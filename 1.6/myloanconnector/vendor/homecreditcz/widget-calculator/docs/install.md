# Home Credit online kalkulačka

> Tuto variantu kalkulačky mohou využít pouze vázaní partneři. Pro nevázané partnery ("tipaře") slouží standalone varianta kalkulačky - více info např. [zde](https://github.com/homecreditcz/oneclick-api/wiki/Produk%C4%8Dn%C3%AD-prost%C5%99ed%C3%AD))

Kalkulačka (jejíž kód běží v prohlížeči zákazníka) potřebuje získat data o možných splátkách. Pomocí HTTP GET provolá odpovídající API endpointy Home Creditu. Tyto požadavky musí obsahovat odpovídající API key HTTP hlavičku.

## Instalace software Kalkulačky

Vlastní kód kalkulačky je třeba nahrát na webový server. Jedná se o soubory JS, styly a ikony v adresáři hc-calc. Webový server je třeba nakonfigurovat tak, aby požadavky na soubory v dané cestě (pod `/hc-calc/*`) korektně obsluhoval, a to včetně korektních hlaviček Content-type.

Příklad: https://eshop.example.com/hc-calc/js/app.js

## Úprava webových stránek e-shopu

Na stránku s produkty, u kterých si zákazník může zvolit nákup na splátky, je třeba:

### 1. Přidat tlačítko (či obdobný prvek) "Nákup na splátky",

které vyvolá pro dané zboží kalkulačku. HTML element bude mít nastaveno zpracování události onClick - spuštění pomocné funkce showCalc.

```javascript
<button onclick="showCalc()">Nákup na splátky</button>
```

### 2. Naimplementovat pomocnou funkci `showCalc()`, 

která zjistí potřebné údaje a zavolá dodanou funkci `showHcCalc(productSetCode, price, downPayment, fixDownPayment, dataCalculatorBaseUrl, apiKey, processCalcResult);`

- `productSetCode` - konstanta dodaná HC 
  - pro testovací účely:
    - CZ: ***COCHCONL***
    - SK: ***COCHCONL***
    
> Tento parametr udává produktovou sadu Home Creditu, jenž se má pro výpočet použít - pokud je zboží zařazeno do speciální akce (např. "Za 0%"), na které se vztahuje kalkulace pod akční produktovou sadou, je potřeba tuto sadu v tomto kroku použít - **Je žádoucí, aby ve správě produktů e-shopu byla možnost tuto vlastnost jednoduše nastavovat (alternativou je mít tuto možnost např. pro vybranou kategorii produktů). Výběr konkrétního produktu pak vede na použití akční produktové sady (`productSetCode`) při inicializaci kalkulačky**

- `price` - cena daného zboží (košíku)

- `downPayment` - hodnota akontace; může být 0

- `fixDownPayment` – vypnutí podpory volitelné akontace

- `dataCalculatorBaseUrl` – pevně daná URL dodaná HC
  - pro testovací účely: 
    - CZ: `https://apicz-test.homecredit.net/verdun-train/public/v1/calculator/`
    - SK: `https://apisk-test.homecredit.net/verdun-train/public/v1/calculator/`
  - na produkčním prostředí: 
    - CZ: `https://api.homecredit.cz/public/v1/calculator/`

- `apiKey` – API klíč, konstanta dodaná HC
  - pro testovací účely:
    - CZ: ***calculator_test_key*** / ***calculator_test_key_dp*** (s podporou pro akontace)
    - SK: ***calculator_test_key*** / ***calculator_test_key_dp*** (s podporou pro akontace)
  - na produkčním prostředí:
    - obdržíte od zodpovědné osoby z HC po schválení vaší implementace

- `processCalcResult` - JS funkce, která se zavolá, když si zákazník zvolí některou z nabízených možností splácení.

```javascript
// helper function example  
function showCalc() {        
  var productSetCode = 'COCHCONL'; 
  var price = document.getElementById('my-product-price').value;        
  var downPayment = 0;         
  var fixDownPayment = true;        
  var dataCalculatorBaseUrl = 'https://apicz-test.homecredit.net/verdun-train/public/v1/calculator/';        
  var apiKey = 'calculator_test_key';
  showHcCalc(productSetCode, price, downPayment, fixDownPayment, dataCalculatorBaseUrl, apiKey, processCalcResult);  
}
```

### 3. Naimplementovat funkci `processCalcResult(calcResult)`,

která zpracuje výsledky z kalkulačky. Typicky uloží údaje pro pozdější použití a přesune zákazníka do Košíku. Příklad objektu `calcResult`, který je funkci předán jako parametr:

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

### 4. Naimportovat styly a fonty pro danou html stránku

```html
<head>
  <link rel="stylesheet" href="hc-calc/style/style.css">
  
  <!-- Typekit Acumin Pro font initialization -->
  <script src="https://use.typekit.net/mxi3qpt.js"></script>
  <script>try { Typekit.load({ async: true }); } catch (e) { }</script>
  ...
</head>
```

### 5. Naimportovat skripty kalkulačky

```html
<script src="hc-calc/js/resize.js"></script>
<script src="hc-calc/js/appLoader.js"></script>
```

### 6. Vložit container kalkulačky do <body> html stránky

```html
<div id="hc-calc-container">
 <div id="hc-calc-modal" class="hc-modal" role="dialog" style="display: none">
   <div class="hc-modal__dialog">
     <div class="hc-modal__content">
       <div id="hc-modal-header" class="hc-modal__header">
         <a id="hc-close-button" href="JavaScript:void(0)" class="hc-modal__close" 
             onclick="document.getElementById('hc-calc-modal').style.display = 'none'"></a>
         <div class="hc-modal__logo">
           <img src="hc-calc/img/logo.svg" alt="logo" />
         </div>
         <div class="hc-modal__title">NÁKUP NA SPLÁTKY</div>
       </div>
       <div id="hc-calculator-wrapper" class="hc-modal-body" ></div>
     </div>
   </div>
 </div>
</div>
```

### 7. Případná úprava zobrazení kalkulačky 
Když má eshop fixní menu, je potřeba zvětšit odsazení kalkulačky od vrchní hrany obrazovky ==> na classu: `"hc-modal"` přidat `"padding-top"`
