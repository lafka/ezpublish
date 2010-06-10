{literal}
<script type="text/javascript">
(function() {
{/literal}

var confObj = {ldelim}


{switch match=$node.sort_field}
{case match='2'}    sortKey: "published_date",{/case}
{case match='3'}    sortKey: "modified_date",{/case}
{case match='4'}    sortKey: "section",{/case}
{case match='7'}    sortKey: "class_name",{/case}
{case match='8'}    sortKey: "priority",{/case}
{case match='9'}    sortKey: "name",{/case}
{case}    sortKey: "published_date",{/case}
{/switch}

    dataSourceURL: "{concat('ezjscore/call/ezjscnode::subtree::', $node.node_id)|ezurl('no')}",
    rowsPrPage: {$number_of_items},
    sortOrder: {$node.sort_order},
    currentURL: "{$node.url|wash( javascript )}",
    previewURL: {"/content/versionview/%objectID%/%version%"|ezurl},
    editURL: {"/content/edit/%objectID%"|ezurl},
    hiddenColumns: "{ezpreference( 'admin_hidden_columns' )}".split(',')
{rdelim}


var labelsObj = {ldelim}


    DATA_TABLE: {ldelim}

                        msg_loading: "{'Loading ...'|i18n( 'design/admin/node/view/full' )|wash('javascript')}"
                    {rdelim},

    DATA_TABLE_COLS: {ldelim}

                        name: "{'Name'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        visibility: "{'Visibility'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        type: "{'Type'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        modifier: "{'Modifier'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        modified: "{'Modified'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        published: "{'Published'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        section: "{'Section'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        priority: "{'Priority'|i18n( 'design/admin/node/view/full' )|wash('javascript')}"
                    {rdelim},

    TABLE_OPTIONS: {ldelim}

                        header: "{'Table options'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        header_noipp: "{'Number of items per page:'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        header_vtc: "{'Visible table columns:'|i18n( 'design/admin/node/view/full' )|wash('javascript')}"
                   {rdelim},

    ACTION_BUTTONS: {ldelim}

                        select: "{'Select'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        select_sav: "{'Select all visible'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        select_sn: "{'Select none'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        create_new: "{'Create new'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        more_actions: "{'More actions'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        more_actions_rs: "{'Remove selected'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        more_actions_ms: "{'Move selected'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        more_actions_no: "{'Use the checkboxes to select one or more items.'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        table_options: "{'Table options'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        first_page: "&laquo;&nbsp;{'first'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        previous_page: "&lsaquo;&nbsp;{'prev'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        next_page: "{'next'|i18n( 'design/admin/node/view/full' )|wash('javascript')}&nbsp;&rsaquo;",
                        last_page: "{'last'|i18n( 'design/admin/node/view/full' )|wash('javascript')}&nbsp;&raquo;"
                    {rdelim},

    CONTEXT_MENU: {ldelim}

                        preview: "{'Preview'|i18n( 'design/admin/node/view/full' )|wash('javascript')}",
                        edit: "{'Edit'|i18n( 'design/admin/node/view/full' )|wash('javascript')}"
                    {rdelim},

{rdelim};

{if and( $node.is_container,  $node.can_create)}
    {if $node.path_array|contains( ezini( 'NodeSettings', 'MediaRootNode', 'content.ini' ) )}
        {def $group_id = array( ezini( 'ClassGroupIDs', 'Users', 'content.ini' ),
                                ezini( 'ClassGroupIDs', 'Setup', 'content.ini' ) )}
    {elseif $node.path_array|contains( ezini( 'NodeSettings', 'UserRootNode', 'content.ini' ) )}
        {def $group_id = array( ezini( 'ClassGroupIDs', 'Setup', 'content.ini' ),
                                ezini( 'ClassGroupIDs', 'Content', 'content.ini' ),
                                ezini( 'ClassGroupIDs', 'Media', 'content.ini' ) )}
    {else}
        {def $group_id = false()}
    {/if}

    {def $can_create_classes = fetch( 'content', 'can_instantiate_class_list', hash( 'parent_node', $node,
                                                                                     'filter_type', 'exclude',
                                                                                     'group_id', $group_id,
                                                                                     'group_by_class_group', true() ) )}

    var createGroups = [
    
    {foreach $can_create_classes as $group}
        "{$group.group_name}"
        {delimiter}
        ,
        {/delimiter}
    {/foreach}
    
    ];
    
    var createOptions = [
    
    {foreach $can_create_classes as $group}
        [
        {foreach $group.items as $can_create_class}
            {if $can_create_class.can_instantiate_languages}
            {ldelim} text: "{$can_create_class.name|wash()}", value: {$can_create_class.id} {rdelim}

            {delimiter},{/delimiter}
            {/if}
        {/foreach}
        ]
        {delimiter}
        ,
        {/delimiter}
    {/foreach}
    ];
    
{else}
    var createGroups = [];
    var createOptions = [];
{/if}

{literal}
YUILoader.require(['datatable', 'button', 'container']);
YUILoader.onSuccess = function() {
    var ss = sortableSubitems();
    ss.init(confObj, labelsObj, createGroups, createOptions);
};
var options = [];
YUILoader.insert(options, 'js');

})();
{/literal}

</script>

<div id="action-controls-container">
    <div id="action-controls"></div>
    <div id="tpg"></div>
</div>
<div id="content-sub-items-list" class="content-navigation-childlist"></div>
<div id="bpg"></div>

<div id="to-dialog-container"></div>
