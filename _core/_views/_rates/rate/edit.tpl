{extends file="_core.3col.tpl"}

{block name="asu_center"}
    <h2>Редактирование ставки</h2>

    {CHtml::helpForCurrentPage()}

    {include file="_rates/rate/form.tpl"}
{/block}

{block name="asu_right"}
    {include file="_rates/rate/add.right.tpl"}
{/block}