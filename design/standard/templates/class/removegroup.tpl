<div class="warning">
<h2>{"Are you sure you want to remove these groups?"|i18n("design/standard/class/edit")}</h2>
<ul>
{section name=Result loop=$DeleteResult}
	<li>{"Removing group %1 will remove %2!"|i18n("design/standard/class/edit",,hash("%1",$Result:item.groupName,"%2",$Result:item.deletedClassName))}</li>
{/section}
</ul>
</div>
<form action={concat($module.functions.removegroup.uri)|ezurl} method="post" name="GroupRemove">

<div class="buttonblock">
{include uri="design:gui/button.tpl" name=Store id_name=ConfirmButton value="Confirm"|i18n("design/standard/class/edit")}
{include uri="design:gui/button.tpl" name=Discard id_name=CancelButton value="Cancel"|i18n("design/standard/class/edit")}
</div>

</form>
