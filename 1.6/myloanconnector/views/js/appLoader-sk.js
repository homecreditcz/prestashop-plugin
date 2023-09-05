/**
 *  @author HN Consulting Brno s.r.o
 *  @copyright  2019-*
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

  let path = "";

  // Urèit správnou cestu podle verze PrestaShopu (1.6 nemá definováno prestashop)
  try {
    path = prestashop.urls.base_url;
  } catch {

    if(typeof(baseDir) !== "undefined") {
      path = baseDir;
    } else {
      path = window.location.origin;
      console.log("Nelze urcit cestu k e-shopu standardne, zkousim: ", path)
    }

  }

  // Definovat cestu k modulu
  path = path + '/modules/myloanconnector/dist/hc-calc-SK/';

  var script = doc.createElement('script');
  script.charset = 'UTF-8';
  script.type = 'text/javascript';
  script.src = path + 'js/app.js';
  body.appendChild(script);

  var appContainer = doc.createElement('div');
  appContainer.setAttribute('id', 'app');
  wrapper.appendChild(appContainer);

  var resizeScript = doc.createElement('script');
  resizeScript.src = path + 'js/resize.contentWindow.js';
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

  var appStarter = doc.createElement('script');
  appStarter.type = 'text/javascript';
  var parameters = createCalculatorParameters(productSetCode, price, downPayment, fixDownPayment, dataCaldulatorBaseUrl, apiKey);
  appStarter.innerHTML = 'var appLoaderInterval = setInterval(function() {if(typeof hcCalculator !== \'undefined\') {hcCalculator.loadApp('+parameters+',callback); clearInterval(appLoaderInterval);}}, 100);';
  body.appendChild(appStarter);

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

function callback(calculation) {
  window.parent.postMessage({ 'type': 'hc-calculator-widget-result', payload: calculation }, '*');
}
