if (typeof alreadyExecuted !== "undefined") {

    console.warn("Myloanconnector loader already executed.");

} else {
    let priceElement = "";
    let calculatorButton = null;
    let productPriceVariant = null;
    let minimalPrices = null;
    let isoCode = null;
    let observer = null;
    document. addEventListener("DOMContentLoaded", function(){
    var alreadyExecuted = true;
    console.info("Myloanconnector started.");

    window.priceElement = getPriceElement();
    window.calculatorButton = document.getElementById("hc-calc-button");
    window.productPriceVariant = null;
    window.minimalPrices = JSON.parse(minimalPrice);
    window.isoCode = getIsoCode();

    // Init
    refreshPriceAndButton();
    setInterval(refreshPriceAndButton, 1000); // Delayed refresh

    window.observer = new MutationObserver(refreshPriceAndButton);
        window.observer.observe(window.priceElement, {childList: true});


    // Functions




    function getIsoCode() {

        if (typeof currency === 'undefined' && typeof currencySign !== 'undefined') {

            switch (currencySign) {
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

    function getPriceElement() {

        // List of known price IDs
        const priceIDs = ["our_price_display", "bothprices_"];


        for (let i = 0; i < priceIDs.length; i++) {

            let element = document.getElementById(priceIDs[i]);

            if (typeof element !== "undefined") {
                return element;
            }

        }

        console.error("Myloanconnector price ID not detected.");

    }


    function refreshPriceAndButton() {

        window.productPriceVariant = Math.round(parseFloat(window.priceElement.textContent.replace(/ /g, '').replace(/,/g, '.')) * 100);

        if (window.productPriceVariant >= (window.minimalPrices[window.isoCode] * 100)) {
            window.calculatorButton.style.display = "";
        } else {
            window.calculatorButton.style.display = "none";
        }

    }



    });
    function htmlDecode(input) {
        var doc = new DOMParser().parseFromString(input, "text/html");
        return doc.documentElement.textContent;
    }
    function showCalc() {
        let calcUrl = htmlDecode(window.calcUrl);
        calcUrl = calcUrl.replace(/%price_placeholder%/g, window.productPriceVariant);

        if (window.isCertified) {
            let apiKey = window.apiKey;
            showHcCalc(window.productSetCode, window.productPriceVariant, 0, false, window.calcUrl, window.apiKey, processCalcResult);
        } else {
            let dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
            let dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;
            let width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
            let height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
            let w = 800;
            let h = 550;
            let systemZoom = width / window.screen.availWidth;
            let left = (width - w) / 2 / systemZoom + dualScreenLeft;
            let top = (height - h) / 2 / systemZoom + dualScreenTop;

            window.open(window.calcUrl, '_blank', 'toolbar=no, location=no, directories=no, status=no, menubar=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
        }
    }

    if (isCertified) {
        function processCalcResult(calcResult) {
            //ajaxCart.add(productId, null, false, this);
            calcResult.productPrice = productPriceVariant;
            $.post(window.calcPostUrl, {hc_calculator: JSON.stringify(calcResult)});
            $.cookie("hc_calculator", JSON.stringify(calcResult));
        }
    }
}