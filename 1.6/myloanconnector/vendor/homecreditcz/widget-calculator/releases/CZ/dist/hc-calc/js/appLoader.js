function showHcCalc(productSetCode, price, downPayment, fixDownPayment, baseUrl, apiKey, callbackFunction) {
  var iframe = document.createElement('iframe');
  iframe.setAttribute('id', 'hc-calc-iframe');
  iframe.setAttribute('frameborder', '0');
  var parameters = createCalculatorParameters(productSetCode, price, downPayment, fixDownPayment, baseUrl, apiKey);
  iframe.setAttribute('onload', 'hcCalcloaded('+parameters+')');

  var wrapper = document.getElementById('hc-calculator-wrapper');
  while (wrapper.firstChild)
    wrapper.removeChild(wrapper.firstChild);

  wrapper.appendChild(iframe);

  registerCallback(callbackFunction);
}

function hcCalcloaded(baseUrl, apiKey, productSetCode, price, downPayment, fixDownPayment) {
  var doc = document;
  var iframe = doc.getElementById("hc-calc-iframe");
  if (iframe === null) return;
  var innerDoc = iframe.contentDocument || iframe.contentWindow.document;

  var head = innerDoc.head;
  var linkFont = doc.createElement('link');
  linkFont.href = "https://fonts.googleapis.com/css?family=Open+Sans:400,700";
  linkFont.rel = "stylesheet";
  head.appendChild(linkFont);

  var script = doc.createElement('script');
  script.charset = 'UTF-8';
  script.type = 'text/javascript';
  script.src = 'hc-calc/js/app.js';
  head.appendChild(script);

  var resizeScript = doc.createElement('script');
  resizeScript.src = 'hc-calc/js/resize.contentWindow.js';
  head.appendChild(resizeScript);

  var body = innerDoc.body;
  var wrapper = doc.createElement('div');
  wrapper.setAttribute('data-iframe-height', '');
  body.appendChild(wrapper);

  var appContainer = doc.createElement('div');
  appContainer.setAttribute('id', 'app');
  wrapper.appendChild(appContainer);

  var appStarter = doc.createElement('script');
  appStarter.type = 'text/javascript';
  var parameters = createCalculatorParameters(productSetCode, price, downPayment, fixDownPayment, baseUrl, apiKey);
  appStarter.innerHTML = 'var appLoaderInterval = setInterval(function() {if(typeof hcCalculator !== \'undefined\') {hcCalculator.loadApp('+parameters+',callback); clearInterval(appLoaderInterval);}}, 100);';
  body.appendChild(appStarter);

  var callbackScript = doc.createElement('script');
  callbackScript.charset = 'UTF-8';
  callbackScript.type = 'text/javascript';
  callbackScript.innerHTML = callback.toString();
  body.appendChild(callbackScript);

  var isOldIE = (navigator.userAgent.indexOf("MSIE") !== -1); // Detect IE10 and below
  iFrameResize({ heightCalculationMethod: isOldIE ? 'max' : 'taggedElement', checkOrigin: false, interval: -1 }, '#hc-calc-iframe');
  document.getElementById('hc-calc-modal').style.display = 'block';
}

function createCalculatorParameters(productSetCode, price, downPayment, fixDownPayment, baseUrl, apiKey) {
  return '\'' + escapeJs(baseUrl) + '\',\''
              + escapeJs(apiKey) + '\',\''
              + escapeJs(productSetCode) + '\','
              + price + ','
              + downPayment + ','
              + fixDownPayment;
}

function escapeJs(text) {
  var map = {
    "'": '\\\'',
    "\\": '\\\\'
  };

  return text.toString().replace(/['\\]/g, function (m) { return map[m]; });
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

function bindEvent(element, eventName, eventHandler) {
  if (element.addEventListener) {
    element.addEventListener(eventName, eventHandler, false);
  } else if (element.attachEvent) {
    element.attachEvent('on' + eventName, eventHandler);
  }
}

function callback(calculation) {
  window.parent.postMessage({ 'type': 'hc-calculator-widget-result', payload: calculation }, '*');
}
