{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="hc-calc-button" style="display: none; text-align: center;">
    <img src="{$hcLogo}">
    <a href="javascript:showCalc()">{l s='Installment calculator' mod='myloanconnector' }</a>
</div>

{if $isCertified == true}
    {include "./hc-calculator-dialog.tpl"}
{/if}

{*<script type="text/javascript">

    if( typeof alreadyExecuted !== "undefined" ) {

        console.warn("Myloanconnector loader already executed.");

    } else {

        var alreadyExecuted = true;
        console.info("Myloanconnector started.");

        let priceElement = getPriceElement();
        let calculatorButton = document.getElementById("hc-calc-button");
        let productPriceVariant = null;
        let minimalPrices = {json_encode(minimalPrice)};
        let isoCode = getIsoCode();

        // Init
        refreshPriceAndButton();
        setInterval(refreshPriceAndButton, 1000); // Delayed refresh

        let observer = new MutationObserver(refreshPriceAndButton);
        observer.observe(priceElement, { childList: true });


        // Functions


        function htmlDecode(input) {
            var doc = new DOMParser().parseFromString(input, "text/html");
            return doc.documentElement.textContent;
        }

        function getIsoCode(){

            if(typeof currency === 'undefined' && typeof currencySign !== 'undefined') {

                switch(currencySign){
                    case '€':
                        return "EUR";
                    break;
                    default:
                        return "CZK";
                    break;
                }

            } else {

                return currency.iso_code;

            }
        }

        function getPriceElement(){

            // List of known price IDs
            const priceIDs = ["our_price_display", "bothprices_"];


            for(let i = 0; i < priceIDs.length; i++){

                let element = document.getElementById(priceIDs[i]);

                if(typeof element !== "undefined"){
                    return element;
                }

            }

            console.error("Myloanconnector price ID not detected.");

        }


        function refreshPriceAndButton() {

            productPriceVariant = Math.round(parseFloat(priceElement.textContent.replace(/ /g,'').replace(/,/g,'.'))*100);

            if(productPriceVariant >= (minimalPrices[isoCode] * 100)){
                calculatorButton.style.display = "";
            } else {
                calculatorButton.style.display = "none";
            }

        }


        function showCalc() {
            let calcUrl = htmlDecode('{urldecode(calcUrl)}');
            calcUrl = calcUrl.replace(/%price_placeholder%/g, productPriceVariant);

            if(isCertified) {
                let apiKey = apiKey;
                showHcCalc(productSetCode, productPriceVariant, 0, false, calcUrl, apiKey, processCalcResult);
            }
            else {
                let dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
                let dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;
                let width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                let height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
                let w = 800;
                let h = 550;
                let systemZoom = width / window.screen.availWidth;
                let left = (width - w) / 2 / systemZoom + dualScreenLeft;
                let top = (height - h) / 2 / systemZoom + dualScreenTop;

                window.open(calcUrl, '_blank', 'toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
            }
        }
        if(isCertified) {
            function processCalcResult(calcResult) {
                //ajaxCart.add(productId, null, false, this);
                calcResult.productPrice = productPriceVariant;
                $.post(calcPostUrl, {literal}{hc_calculator: JSON.stringify(calcResult)});{/literal}
                $.cookie("hc_calculator", JSON.stringify(calcResult));
            }
        }
    }
</script>*}

