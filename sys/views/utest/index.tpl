<!doctype html><html lang="en"><head>
    <title>Swale Unit Test Results</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <link rel="stylesheet" href="http://cdn.bootcss.com/uikit/2.16.2/css/uikit.almost-flat.min.css" />
    <style type="text/css">
        .clsOKRow{
            color: black;
            background-color: green;
        }
        .clsNGRow{
            color: white;
            font-weight: bold;
            background-color: red;
        }
        .clsOKRow a, .clsNGRow a{
            color: white;
            text-decoration:none;
        }
        .clsOKRow a:hover, .clsNGRow a:hover{
            color: white;
            text-decoration:none;
        }
        .clsOKChk{
        }
        .clsNGChk{
        }
    </style>
</head><body page_id="swale_unit_test">
    <br />
    <div class="uk-container uk-container-center">
        <h2 class="uk-container-center"><strong><span class="uk-icon-puzzle-piece" style="color: green;"></span>Swale Unit Test Results ({$module}):</strong> -- {$time_stamp}</h2>
        <table class="uk-table uk-table-condensed">
            <thead>
                <tr>
                    <th class="uk-width-1-10">Function</th>
                    <th class="uk-width-1-10">Ratio</th>
                    <th class="uk-width-1-10">Result</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$utest key=func item=test}<tr class="{if $test.assert_bad>0}clsNGRow{else}clsOKRow{/if}">
                    <td><strong><a href="{$base_url}{$func}" title="Time : {$test.time_cost}">{$func}</a></strong></td>
                    <td>{$test.assert_good}/{$test.assert_total}</td>
                    <td>{if $test.assert_bad>0}NG{else}OK{/if}</td>
                    <td>
                        {foreach from=$test.results item=test_case}<a class="{if $test_case.result}uk-icon-check clsOKChk{else}uk-icon-close clsNGChk{/if}" title="{$test_case.function} : {$test_case.message}"></a>{/foreach}
                    </td>
                </tr>{/foreach}
            </tbody>
        </table>
    </div>

<!--[if lte IE 9]>
    <script src="http://www.geexfinance.com/merchant/bower_components/respond/respond.min.js"></script>
    <script src="http://www.geexfinance.com/merchant/bower_components/html5shiv/html5shiv.min.js"></script>
<![endif]-->

<script type="text/javascript" src="http://cdn.bootcss.com/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript" src="http://cdn.bootcss.com/uikit/2.16.2/js/uikit.js"></script>
</body></html>
