# Home Credit online kalkulačka

## Instalace software Kalkulačky
Vlastní kód kalkulačky je třeba nahrát na webový server.
Jedná se o soubory JS, styly a ikony v adresáři hc-calc.
Webový server je třeba nakonfigurovat tak, aby požadavky na soubory v dané cestě (pod `/hc-calc/*`) korektně obsluhoval, a to včetně korektních hlaviček Content-type.

Příklad: `https://eshop.example.com/hc-calc/js/app.js`


## Integrace s e-shopem

### Úprava webových stránek e-shopu

Na stránku s produkty, u kterých si zákazník může zvolit nákup na splátky, je třeba:

1. přidat tlačítko (či obdobný prvek) "Nákup na splátky", které vyvolá pro dané zboží kalkulačku.
HTML element bude mít nastaveno zpracování události onClick - spuštění pomocné funkce showCalc.
```javascript
<button onclick="showCalc()">Nákup na splátky</button>
```
2. naimplementovat pomocnou funkci `showCalc()`, která zjistí potřebné údaje a zavolá dodanou funkci `showHcCalc(salesRoomCode, productSet, price, downPayment, fixDownPayment, dataCalculatorBaseUrl, processCalcResult);`

	- productSetCode - konstanta dodaná HC
	- price - cena daného zboží
	- downPayment - hodnota akontace; může být 0
  - fixDownPayment - true / false, parametr, který zakáže nastavení akontace uživatelem a počítá, že je 0
	- dataCalculatorBaseUrl - základ URL, ke kterému se přidá 'api/calculate' respektive 'api/initpayrange' pro volání daných služeb. Např.: `https://eshop.example.com/`
  - apiKey - klic uctu, konstanta, dodávaná HC
	- processCalcResult - JS funkce, která se zavolá, když si zákazník zvolí některou z nabízených možností splácení.

```javascript
	// helper function example
    function showCalc() {
      var productSetCode = 'CO123'; // example
      var price = 14000; // number in minor units
      var downPayment = 00; // number in minor units
      var fixDownPayment = true; // parameter to decide if enable od disable downpayment
      var dataCalculatorBaseUrl = 'http://eshop.example.com/';
      var apiKey = 'calculator_test_key';
      showHcCalc( productSetCode, price, downPayment, fixDownPayment, dataCalculatorBaseUrl,apiKey, processCalcResult);
    }

```

3. naimplementovat funkci `processCalcResult(calcResult)`, která zpracuje výsledky z kalkulačky. Typicky uloží údaje pro pozdější použití a přesune zákazníka do Košíku.
Příklad objektu calcResult, který je funkci předán jako parametr:
```javascript
function processCalcResult(calcResult) {
	console.log(calcResult);
	// store the values ...
	// go to basket ...
}

// calcResult object example:
{
	annualInterestRate: 19.97,
	creditAmount: 1300000, // minor units
	creditTotalRepay: 1554000, // minor units
  preferredDownPayment: 100000, // minor units
	legalLine: "Každou žádost posuzujeme...",
	preferredInstallment: 129500, // minor units
	productCode: "COCONL12",
	annualPercentageRate: 22,
	preferredMonths: 12
}
```

4. naimportovat styly a fonty pro danou html stránku
```html
<head>
  <link rel="stylesheet" href="hc-calc/style/style.css">
  ...
</head>
```

5. naimportovat skripty kalkulačky
```html
  <script src="hc-calc/js/resize.js"></script>
  <script src="hc-calc/js/appLoader.js"></script>
```

6. vložit container kalkulačky
```html
  <div id="hc-calc-container">
    <div id="hc-calc-modal" class="hc-modal" role="dialog" style="display: none">
      <div class="hc-modal__dialog">
        <div class="hc-modal__content">
          <div id="hc-modal-header" class="hc-modal__header">
            <a id="hc-close-button" href="JavaScript:void(0);" class="hc-modal__close" onclick="document.getElementById('hc-calc-modal').style.display = 'none'"></a>
            <div class="hc-modal__logo">
              <img src="hc-calc/img/logo.svg" alt="logo" />
            </div>
            <div class="hc-modal__title">NÁKUP NA SPLÁTKY</div>
          </div>
          <div id="hc-calculator-wrapper" class="hc-modal__body"></div>
        </div>
      </div>
    </div>
  </div>
```

7. Případná úprava zobrazení kalkulačky
7.1. Když má eshop fixní menu, je potřeba zvětšit odsazení kalkulačky od vrchní hrany obrazovky. Tzn.: na classu: "hc-modal" přidat "padding-top"

## API

### Úprava API služeb e-shopu
Kalkulačka (jejíž kód běží v prohlížeči zákazníka) potřebuje získat data o možných splátkách.
Pomocí HTTP GET provolá odpovídající API endpoint Home Creditu. Tento požadavek musí obsahovat odpovídající Api key HTTP hlavičku

### Definice
[Aktuální definice API](https://intranet.finance.homecredit.cz/download/attachments/103787108/Public%20API%20%283%20endpointy%29%20.apib?api=v2)

### Kontaktní osoba
Jakub Klos (jakub.klos@homecredit.cz)
