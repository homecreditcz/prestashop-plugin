function bindEvent(element, eventName, eventHandler) {
  if (element.addEventListener) {
    element.addEventListener(eventName, eventHandler, false);
  } else if (element.attachEvent) {
    element.attachEvent('on' + eventName, eventHandler);
  }
}

function ready(fn) {
  if (document.readyState != 'loading') {
    fn();
  } else if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', fn);
  } else {
    document.attachEvent('onreadystatechange', function () {
      if (document.readyState != 'loading')
        fn();
    });
  }
}

function registerCallback(callbackFunc) {
  if (registerCallback.registered === undefined) {
    ready(function () {
      bindEvent(window, 'message', function (e) {
        if (e.data.type && e.data.type == 'hc-calculator-widget-result') {
          callbackFunc(e.data.payload);
          document.getElementById('hc-calc-modal').style.display = 'none';
        }
      });
    });
    registerCallback.registered = true;
  }
}

function showHcCalc(productSetCode, price, downPayment, fixDownPayment, dataCalculatorBaseUrl, apiKey, callbackFunction) {
  var wrapper = document.getElementById('hc-calculator-wrapper');
  var iframe = document.createElement('iframe');
  iframe.setAttribute('id', 'hc-calc-iframe');
  iframe.setAttribute('frameborder', '0');

  var e = 'hcCalcloaded(\'' + escapeJs(productSetCode) + '\', \'' + escapeJs(price) + '\', \'' + escapeJs(downPayment) + '\', \'' + escapeJs(fixDownPayment) + '\',\'' + escapeJs(dataCalculatorBaseUrl) +'\',\'' + escapeJs(apiKey) + '\')';

  iframe.setAttribute("onload", e);

  while (wrapper.firstChild)
    wrapper.removeChild(wrapper.firstChild);
  wrapper.appendChild(iframe);

  registerCallback(callbackFunction);
}


function hcCalcloaded(productSetCode, price, downPayment, fixDownPayment, dataCaldulatorBaseUrl,apiKey) {
  var doc = document;
  var iframe = doc.getElementById("hc-calc-iframe");
  if (iframe === null) return;
  var innerDoc = iframe.contentDocument || iframe.contentWindow.document;
  var body = innerDoc.body;
  var wrapper = doc.createElement('div');
  wrapper.setAttribute('data-iframe-height', '');

  var div = doc.createElement('div');
  div.setAttribute('id', 'hc-calculator-widget');
  div.setAttribute('data-calculator-productSetCode', productSetCode);
  div.setAttribute('data-calculator-price', price);
  div.setAttribute('data-calculator-downPayment', downPayment);
  div.setAttribute('data-calculator-fixDownPayment', fixDownPayment);
  div.setAttribute('data-calculator-onSubmit', 'callback');
  div.setAttribute('data-calculator-baseUrl', dataCaldulatorBaseUrl);
  div.setAttribute('data-calculator-apiKey', apiKey);

  wrapper.appendChild(div);
  body.appendChild(wrapper);

  var script = doc.createElement('script');
  script.charset = 'UTF-8';
  script.type = 'text/javascript';
  script.src = 'hc-calc/js/app.js';
  body.appendChild(script);

  var resizeScript = doc.createElement('script');
  resizeScript.src = 'hc-calc/js/resize.contentWindow.js';
  body.appendChild(resizeScript);

  var callbackScript = doc.createElement('script');
  callbackScript.charset = 'UTF-8';
  callbackScript.type = 'text/javascript';
  callbackScript.innerHTML = callback.toString();
  body.appendChild(callbackScript);

  var linkFont = doc.createElement('link');
  linkFont.href = "https://fonts.googleapis.com/css?family=Open+Sans:400,700";
  linkFont.rel = "stylesheet";
  body.appendChild(linkFont);

  var head = innerDoc.head;
  var fontScript = document.createElement('script');
  fontScript.src = "https://use.typekit.net/mxi3qpt.js";
  fontScript.type = 'text/javascript';
  fontScript.charset = 'UTF-8';
  head.appendChild(fontScript);

  var fontLoad = doc.createElement('script');
  fontLoad.type = 'text/javascript';
  fontLoad.innerHTML = "try { Typekit.load({ async: true }); } catch (e) { }";
  head.appendChild(fontLoad);

  var isOldIE = (navigator.userAgent.indexOf("MSIE") !== -1); // Detect IE10 and below

  iFrameResize({ heightCalculationMethod: isOldIE ? 'max' : 'taggedElement', checkOrigin: false, interval: -1 }, '#hc-calc-iframe');
  document.getElementById('hc-calc-modal').style.display = 'block';
}

function escapeJs(text) {
  var map = {
    "'": '\\\'',
    "\\": '\\\\'
  };

  return text.toString().replace(/['\\]/g, function (m) { return map[m]; });
}

function callback(calculation) {
  window.parent.postMessage({ 'type': 'hc-calculator-widget-result', payload: calculation }, '*');
}
