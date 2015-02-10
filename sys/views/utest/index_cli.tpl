
#### Swale Unit Test Results ({$module}):####
Function    Ratio   Result  Details
{foreach from=$utest key=func item=test}{if $test.assert_bad>0}{$cli_start_ng}{else}{$cli_start_ok}{/if}{$func}    {$test.assert_good}/{$test.assert_total}    {if $test.assert_bad>0}NG{else}OK{/if}    {foreach from=$test.results item=test_case}{if $test_case.result}.{else}#{/if}{/foreach}
{$cli_end}{/foreach}

