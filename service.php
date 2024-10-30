<?php
if(!defined('ROOT')) exit('No direct script access allowed');

handleActionMethodCalls();

function _service_sources() {
    $srcData = [
            CMS_APPROOT."config/features/codeSnippets.json",
            APPROOT."config/features/codeSnippets.json"
        ];
    
    $sourceData = [];
    foreach($srcData as $f) {
        if(file_exists($f)) {
            $jsonData = json_decode(file_get_contents($f), true);
            if(!$jsonData) $jsonData = [];
            if(!isset($jsonData['sources'])) $jsonData['sources'] = [];
            $sourceData = array_merge_recursive($sourceData, $jsonData);
        }
    }
    if(!$sourceData || count($sourceData['sources'])<=0) {
        $sourceData['sources'] = [
            "github"=>[
                "logiks"
            ]
        ];
    }
    
    foreach($sourceData['sources'] as $k=>$v) {
        $sourceData['sources'][$k] = array_unique($v);
    }
    
    $_SESSION['CODE_SNIPPET_SOURCES'] = $sourceData['sources'];
    
    return $sourceData['sources'];
}

function _service_list() {
    $type = "github";
    $userid = "Logiks";
    
    if(isset($_REQUEST['refid']) && strlen($_REQUEST['refid'])>0) {
        $userid = $_REQUEST['refid'];
    }
    if(isset($_REQUEST['type']) && strlen($_REQUEST['type'])>0) {
        $type = $_REQUEST['type'];
    }
    
    $repoHash = md5($userid.$type);
    
    $repoData = _cache("CODESNIPPETS-{$repoHash}");
    
    if(isset($_GET['recache']) && $_GET['recache']=="true") {
        $repoData = false;
    }
    
    if(!$repoData) {
        $maxPage = 10;
        $repoDataRemote = [];
        
        switch($type) {
            case "github":case "gist":
                for($p = 1; $p<$maxPage; $p++) {
                    $data = fetchGistData($userid, $p);
                    if(!$data || count($data)<=0) break;
                    else {
                        $repoDataRemote = array_merge($repoDataRemote, $data);
                    }
                }
                break;
            default:
        }
        
        _cache("CODESNIPPETS-{$repoHash}", $repoDataRemote);
        $repoData = $repoDataRemote;
    }
    return $repoData;
}

function fetchGistData($user = "Logiks", $page = 0) {
    $url = "https://api.github.com/users/{$user}/gists?page={$page}";
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "utf8",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//       CURLOPT_CUSTOMREQUEST => "GET",
//       CURLOPT_POSTFIELDS => "",
      CURLOPT_HTTPHEADER => array(
          "User-Agent: Awesome-Octocat-App"
    //     "token: ".ESTORE_KEY
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    
    if($err) return [];
    else {
        $response = json_decode($response, true);
        $fData = [];
        
        foreach($response as $repo) {
            $descs = $repo['description'];
            $tags = "";
            
            preg_match('/\[[^\]]*\]/', $descs, $title);
            preg_match_all('/# *([^#\>]+)/', $descs, $tagsArr);
            
            if(isset($title[0]) && strlen($title[0])>0) {
                $title = $title[0];
            } else {
                $title = "";
            }
            
            foreach($tagsArr[0] as $a=>$b) {
                $b = str_replace(">","",$b);
                $tags[] = str_replace(">","",$b);
            }
            $tags = array_unique($tags);
            
            $descs = str_replace($title, "", $descs);
            
            if(strlen($title)>2) {
                $title = substr($title, 1, strlen($title)-2);
            }
            
            $fileList = array_keys($repo['files']);
            
            if($title==null || strlen($title)<=0) {
                if(strlen($descs)<=25) {
                    $title = $descs;
                } else {
                    $length = strpos($descs,",");
                    if(!$length || $length<=0) {
                        $length = strpos($descs, ' ', 20);
                        if(!$length) {
                            $length = 25;
                        }
                    }
                    //if(!$length) $length = 20;
                    $title = substr($descs, 0, $length);
                }
            }
            
            $fData[$repo['id']] = [
                    "title"=>$title,
                    "descs"=>$descs,
                    "tags"=>$tags,
                    "updated_on"=>$repo['updated_at'],
                    "html_url"=>$repo['html_url'],
                    "files"=>$fileList,
                    "files_count"=>count($fileList),
                ];
        }
        
        return $fData;
    }
}
?>