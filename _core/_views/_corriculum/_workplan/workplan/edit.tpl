{extends file="_core.3col.tpl"}

{block name="asu_center"}
    <h2>Редактирование рабочей программы</h2>
    {CHtml::helpForCurrentPage()}
    
    <form action="workplans.php" method="post" enctype="multipart/form-data" class="form-horizontal">
        {CHtml::hiddenField("action", "save")}
        {CHtml::activeHiddenField("discipline_id", $plan)}
        {CHtml::activeHiddenField("corriculum_discipline_id", $plan)}
        {CHtml::activeHiddenField("id", $plan)}

        {CHtml::errorSummary($plan)}
        {CHtml::warningSummary($plan)}

        <ul class="nav nav-tabs">
            <li><a data-toggle="tab" href="#common">Общая информация</a></li>
            <li><a data-toggle="tab" href="#place">Место дисциплины</a></li>
            <li><a data-toggle="tab" href="#competentions">Компетенции</a></li>
            <li class="active"><a data-toggle="tab" href="#content">Содержание</a></li>
            <li><a data-toggle="tab" href="#selfWork">Самостоятельная работа</a></li>
            <li><a data-toggle="tab" href="#markTypes">Оценочные средства</a></li>
            <li><a data-toggle="tab" href="#library">Обеспечение</a></li>
            <li><a data-toggle="tab" href="#changes">Лист изменений и дополнений в рабочей программе</a></li>
            <li><a data-toggle="tab" href="#protocols">Протоколы</a></li>
            <li><a data-toggle="tab" href="#status">Статус</a></li>
        </ul>
        <div class="tab-content">
            <div id="common" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.common.tpl"}
            </div>
            <div id="place" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.place.tpl"}
            </div>
            <div id="competentions" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.competentions.tpl"}
            </div>
            <div id="content" class="tab-pane active">
                {include file="_corriculum/_workplan/workplan/subform.content.tpl"}
            </div>
            <div id="selfWork" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.selfWork.tpl"}
            </div>
            <div id="markTypes" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.markTypes.tpl"}
            </div>
            <div id="library" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.library.tpl"}
            </div>
            <div id="changes" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.changes.tpl"}
            </div>
            <div id="protocols" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.protocols.tpl"}
            </div>
            <div id="status" class="tab-pane">
                {include file="_corriculum/_workplan/workplan/subform.status.tpl"}
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                {CHtml::submit("Сохранить")}
            </div>
        </div>
    </form>
{/block}

{block name="asu_right"}
    {include file="_corriculum/_workplan/workplan/common.right.tpl"}
{/block}
