{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="row" id="hc-confirm-payment-method">
    <div class="col-xs-12">
        <p class="payment_module" id="hc-select-payment">
            <a class="cheque"> <!-- href="{$actionUrl}"-->
                <img src="{$hcLogo|escape:'quotes'}" height="30"/>
                <span>
                {if !empty($loanOverview)}
                    {l s='(Buy in installments)' mod='myloanconnector' }
                    {if !empty($loanOverview)}
                        <span>({l s='Payment'  mod='myloanconnector'} {$loanOverview|escape:'htmlall':'UTF-8'})</span>
                    {/if}

                        {if $isCertified == true}
                            <span id="hc-change-payment" onclick="showCalc()" style="padding: 8px;">{l s='Change calculaton' mod='myloanconnector' }</span>
                        {else}
                            <span id="hc-change-payment" style="padding: 8px;">{l s='Change calculaton' mod='myloanconnector' }</span>
                        {/if}

                    {else}
                    <span onclick="showCalc()">
                        {l s='Buy in installments redirect' mod='myloanconnector' }
                    </span>
                {/if}
                    <br>
                    <br>
                    <span id="hc-info" style="font-size: 14px; text-decoration: underline;" role="button" data-toggle="modal" data-target="#infoModal">
                        {l s='Information on the transmission of data to Home Credit a.s.' mod='myloanconnector' }
                    </span>
                </span>

                {if $isCertified == true}
                    {include "./hc-calculator-dialog.tpl"}
                {/if}
            </a>
        </p>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="">
                <h5 style="float: left;" class="modal-title" id="exampleModalLabel">{l s='Information about data transmission' mod='myloanconnector' }</h5>
                <button style="float: right; padding:0 8px 0 8px;" type="button" class="close" data-dismiss="infoModal" onclick="showInfo()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {l s='Personal Information' mod='myloanconnector' }
            </div>
        </div>
    </div>
</div>

{*{if $isCertified == true}

    <script>

        $(document).ready(function () {
            let body = document.querySelector('body');
            let element = document.getElementById('infoModal');
            if (!!body && !!element) {
                body.appendChild(element);
            }
            $('#hc-select-payment').click(function (e) {

                if(e.originalEvent.target.id === "hc-info"){
                    showInfo();
                    return;
                }
                console.info(e.originalEvent.target);
                if(e.originalEvent.target.id === "hc-info"){
                    showInfo();
                    return;
                }

                if (isLoanCookieValid()) {
                    if(e.originalEvent.target.id === "hc-change-payment"){
                        showCalc();
                    } else {
                        window.location.href = "{$actionUrl|escape:'quotes'}";
                    }
                } else {
                    showCalc();
                }
            });
        });

        function showCalc() {
            let apiKey = '{$apiKey|escape:'quotes'}';
            showHcCalc('{$productSetCode|escape:'htmlall':'UTF-8'}', {$cartOrderTotal|escape:'htmlall':'UTF-8'}, 0, false, '{$calcUrl|escape:'quotes'}', apiKey, processCalcResult);
        }

        function processCalcResult(calcResult) {
            calcResult.productPrice = {$cartOrderTotal|escape:'htmlall':'UTF-8'};
            $.cookie("hc_calculator", JSON.stringify(calcResult));
            $.ajax({
                url: "{$calcPostUrl|escape:'quotes'}",
                data: {literal}{hc_calculator: JSON.stringify(calcResult)}{/literal},
                method: 'POST',
                crossDomain: true,
                dataType: 'json',
                complete: function() {
                    window.location.href = "{$actionUrl|escape:'quotes'}";
                },
            });
        }

        function isLoanCookieValid() {
            console.info(!$.cookie("hc_calculator"));
            if (!$.cookie("hc_calculator")) {
                return false
            }

            let loanCookie = $.parseJSON($.cookie("hc_calculator"));
            return (Math.round(loanCookie.creditAmount) == Math.round({$cartOrderTotal|escape:'quotes'}));
        }
        
        function showInfo() {
            $('#infoModal').modal('toggle');
        }

    </script>
{else}
    <script>

        $(document).ready(function () {

            $('#hc-select-payment').click(function () {
                window.location.href = "{$actionUrl|escape:'quotes'}";
            });
        });

    </script>
{/if}*}
{*moved to header TPL of the module*}

