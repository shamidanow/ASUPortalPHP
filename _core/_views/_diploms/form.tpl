<script>
    jQuery(document).ready(function(){
        jQuery("#tabs").tabs();
    });
</script>

<form action="index.php" method="post">
{CHtml::hiddenField("action", "save")}
{CHtml::activeHiddenField("id", $diplom)}

    <p>{CHtml::errorSummary($diplom)}</p>

    <div id="tabs">
        <ul style="height: 30px; ">
            <li><a href="#before">До защиты</a></li>
            <li><a href="#after">После защиты</a></li>
        </ul>
        <div id="before">
            {include file="_diploms/subform.before.tpl"}
        </div>
        <div id="after">
            {include file="_diploms/subform.after.tpl"}
        </div>
    </div>

    <p>
        {CHtml::submit("Сохранить")}
    </p>
</form>