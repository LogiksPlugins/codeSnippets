<?php
if(!defined('ROOT')) exit('No direct script access allowed');
loadModule("pages");

function pageContentArea() {
    return "<div id='contentArea' class='table-responsive'><table class='table table-responsive'>
    <thead>
        <tr>
            <th>SL#</th>
            <th width=150px>Title</th>
            <th width=150px>Tags</th>
            <th>Description</th>
            <th>Updated On</th>
            <th>Files</th>
        </tr>
    </thead><tbody></tbody></table></div>";
}
function pageSidebar() {
    return "<div id='sidebarArea'></div>";
}

_css(["codeSnippets"]);
printPageComponent(false,[
		"toolbar"=>[
			"recacheSnippetList"=>["icon"=>"<i class='fa fa-retweet'></i>","tips"=>"Recache the snippet lists"],
// 			"loadSnippetList"=>["icon"=>"<i class='fa fa-refresh'></i>","tips"=>"Reload the list"],
//			"findAllIssues"=>["icon"=>"<i class='fa fa-dashboard'></i>","tips"=>"Find all issues in the Packages"],
			//['type'=>"bar"],
			//"rename"=>["icon"=>"<i class='fa fa-terminal'></i>","class"=>"onsidebarSelect onOnlyOneSelect","tips"=>"Rename Content"],
// 			"deleteTemplate"=>["icon"=>"<i class='fa fa-trash'></i>","class"=>"onsidebarSelect"],
		],
		"sidebar"=>false,//"pageSidebar",
		"contentArea"=>"pageContentArea"
	]);
_js(["codeSnippets"]);
?>
<style>
.nowrap {
    white-space: nowrap;
}
.tag {
    display: inline-block;
    white-space: nowrap;
    margin: 2px;
}
.tagcolumn {
    word-break: break-word;
}
</style>
<script>
var sourceList = [];
$(function() {
    $("#pgtoolbar .nav.navbar-left").append("<li style='margin-top: 3px;width: 170px;'><select class='form-control' id='typeDropdown'></select></li>");
	$("#pgtoolbar .nav.navbar-left").append("<li style='margin-top: 3px;width: 170px;margin-left:10px;'><select class='form-control' id='userDropdown'></select></li>");
	
	//$("#typeDropdown").append("<option value='github'>Github Gists</option>");
	//$("#userDropdown").append("<option value='Logiks'>Logiks</option>");
	
	$("#typeDropdown").change(function() {
	    $("#userDropdown").html("");
        $.each(sourceList[$("#typeDropdown").val()], function(k, v) {
            var t = toTitle(v);
            $("#userDropdown").append(`<option value='${v}'>${t}</option>`);
        });
        
	    loadSnippetList();
	});
	$("#userDropdown").change(loadSnippetList);
	
	loadSourceList();
});
function loadSourceList() {
    processAJAXQuery(_service("codeSnippets","sources"), function(data) {
        sourceList = data.Data;
        
        $("#typeDropdown").html("");
        $.each(sourceList, function(src, users) {
            var t = toTitle(src);
            $("#typeDropdown").append(`<option value='${src}'>${t}</option>`);
        });
        $("#userDropdown").html("");
        $.each(sourceList[$("#typeDropdown").val()], function(k, v) {
            var t = toTitle(v);
            $("#userDropdown").append(`<option value='${v}'>${t}</option>`);
        });
        
        loadSnippetList();
    }, "json");
}
function loadSnippetList() {
    $("#contentArea tbody").html("<tr><th colspan=100><div class='ajaxloading ajaxloading5'></div></th></tr>");
    processAJAXPostQuery(_service("codeSnippets","list"), "type="+$("#typeDropdown").val()+"&refid="+$("#userDropdown").val(),function(data) {
        $("#contentArea tbody .ajaxloading").closest("tr").detach();
        
        if(data.Data==null || data.Data.length<=0) {
            $("#contentArea tbody").html("<tr><th colspan=100><h3 align=center>No Snippets found for the account</h3></th></tr>");
        } else {
            $.each(data.Data, function(a, repo) {
                //console.log(repo);
                if(repo.tags==null) tags = "";
                else {
                    tags = "";
                    $.each(repo.tags, function(a,b) {
                        tags += "<label class='tag label label-warning'>"+b+"</label>";
                    });
                }
                nx = $("#contentArea tbody").children().length+1;
                
                html = "<tr><th>"+nx+"</th><td><a href='"+repo.html_url+"' target=_blank>"+repo.title+"</a></td><td width=150px class='tagcolumn'>"+tags+"</td><td>"+repo.descs+"</td><td class='nowrap'>"+repo.updated_on.split("T")[0]+"</td><td>"+repo.files_count+"</td></tr>";
                $("#contentArea tbody").append(html);
            });
        }
    },"json");
}
function recacheSnippetList() {
    if(confirm("Do you want to recache the entire repo, this may take some time?")) {
        $("#contentArea tbody").html("<tr><th colspan=100><div class='ajaxloading ajaxloading5'></div></th></tr>");
        processAJAXQuery(_service("codeSnippets","list")+"&recache=true", function(data) {
            $("#contentArea tbody .ajaxloading").closest("tr").detach();
            
            if(data.Data==null || data.Data.length<=0) {
                $("#contentArea tbody").html("<tr><th colspan=100><h3 align=center>No Snippets found for the account</h3></th></tr>");
            } else {
                $.each(data.Data, function(a, repo) {
                    //console.log(repo);
                    if(repo.tags==null) tags = "";
                    else {
                        tags = "";
                        $.each(repo.tags, function(a,b) {
                            tags += "<label class='tag label label-warning'>"+b+"</label>";
                        });
                    }
                    nx = $("#contentArea tbody").children().length+1;
                    html = "<tr><th>"+nx+"</th><td><a href='"+repo.html_url+"' target=_blank>"+repo.title+"</a></td><td width=150px class='tagcolumn'>"+tags+"</td><td>"+repo.descs+"</td><td class='nowrap'>"+repo.updated_on.split("T")[0]+"</td><td>"+repo.files_count+"</td></tr>";
                    $("#contentArea tbody").append(html);
                });
            }
        },"json");
    }
}
</script>