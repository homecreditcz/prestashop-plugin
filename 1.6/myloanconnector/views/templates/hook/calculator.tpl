{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="box-security" id="hc-calc-button">
    <a href="javascript:showCalc()">{l s='Installment calculator' mod='myloanconnector' }</a>
</div>

{if $isCertified == true}
    {include "./hc-calculator-dialog.tpl"}
{/if}

<script type="text/javascript">

    function showCalc() {
        {if $isCertified == true}
            let apiKey = '{$apiKey|escape:'quotes'}';
            showHcCalc('{$productSetCode|escape:'htmlall':'UTF-8'}', {$productPrice|escape:'htmlall':'UTF-8'}, 0, false, '{$calcUrl|escape:'quotes'}', apiKey, processCalcResult);
        {else}
            let dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
            let dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;
            let width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            let height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
            let w = 800;
            let h = 550;
            let systemZoom = width / window.screen.availWidth;
            let left = (width - w) / 2 / systemZoom + dualScreenLeft;
            let top = (height - h) / 2 / systemZoom + dualScreenTop;
            window.open('{$calcUrl|escape:'quotes'}', '_blank', 'toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no, width='+w+', height='+h+', top='+top+', left='+left);
        {/if}
    }

    {if $isCertified == true}

        function processCalcResult(calcResult) {
            //ajaxCart.add({$productId|escape:'htmlall':'UTF-8'}, null, false, this);
            calcResult.productPrice = {$productPrice|escape:'htmlall':'UTF-8'};
            $.post( "{$calcPostUrl|escape:'quotes'}", {literal}{hc_calculator: JSON.stringify(calcResult)});{/literal}
            $.cookie("hc_calculator", JSON.stringify(calcResult));
        }

    {/if}

</script>
