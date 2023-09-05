$(document).ready(function () {
    let body = document.querySelector('body');
    let element = document.getElementById('infoModal');
    if (!!body && !!element) {
        body.appendChild(element);
    }
    $('#hc-select-payment').click(function (e) {

        if (e.originalEvent.target.id === "hc-info") {
            showInfo();
            return;
        }
        console.info(e.originalEvent.target);
        if (e.originalEvent.target.id === "hc-info") {
            showInfo();
            return;
        }

        if (isLoanCookieValid()) {
            if (e.originalEvent.target.id === "hc-change-payment") {
                showCalc();
            } else {
                window.location.href = window.actionUrl;
            }
        } else {
            showCalc();
        }
    });

});



function showCalc() {
    let apiKey = window.apiKey;
    showHcCalc(window.productSetCode, window.cartOrderTotal, 0, false, window.calcUrl, window.apiKey, processCalcResult);
}

function processCalcResult(calcResult) {
    calcResult.productPrice = window.cartOrderTotal;
    $.cookie("hc_calculator", JSON.stringify(calcResult));
    $.ajax({
        url: calcPostUrl,
        data: {
            hc_calculator: JSON.stringify(calcResult)
        },
        method: 'POST',
        crossDomain:
            true,
        dataType:
            'json',
        complete:

            function () {
                window.location.href = window.actionUrl;
            }

        ,
    });
}

function isLoanCookieValid() {
    console.info(!$.cookie("hc_calculator"));
    //console.info(!Util.getCookie("hc_calculator"));
    //Util.setCookie(DISMISSED_COOKIE, 'yes', this.options.expiryDays, this.options.domain, this.options.path);

    // if (!Util.getCookie("hc_calculator")) {
    if (!$.cookie("hc_calculator")) {
        return false
    }

    // let loanCookie = $.parseJSON(Util.getCookie("hc_calculator"));
    let loanCookie = $.parseJSON(!$.cookie("hc_calculator"));
    return (Math.round(loanCookie.creditAmount) == Math.round(window.cartOrderTotal))
        ;
}

function showInfo() {
    $('#infoModal').modal('toggle');
}