<div class="warning">
<h2>{"Are you sure you want to remove %1 from node %2?"|i18n("design/standard/node",,hash("%1",$object.name,"%2",$node.object.name))}</h2>
<ul>
    <li>{"Removing this assignment will also remove it's %1 children!"|i18n("design/standard/node",,hash("%1",$ChildObjectsCount))}</li>
</ul>
</div>


<form enctype="multipart/form-data" method="post" action={concat("/content/removenode/",$object.id,"/",$edit_version,"/",$node.node_id,"/")|ezurl}>

<h1>{"Removing node assignment of"|i18n("design/standard/node")} {$object.name}</h1>

<input type="hidden" name=RemoveNodeID value={$node.node_id} />
<div class="buttonblock">
{include uri="design:gui/button.tpl" name=Store id_name=ConfirmButton value="Confirm"|i18n("design/standard/node")}
{include uri="design:gui/button.tpl" name=Discard id_name=CancelButton value="Cancel"|i18n("design/standard/node")}
</div>
</form>
