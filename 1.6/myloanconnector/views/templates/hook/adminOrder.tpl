{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-edit"></i>
        HomeCredit
    </div>

    <div class="row">
        <div class="col-md-6">
            <h4>{l s='Status' mod='myloanconnector' }</h4>
            <b>{$text["id"]|escape:'htmlall':'UTF-8'}</b> {$loanDetail->getApplicationId()}<br>
            <b>{$text["state"]|escape:'htmlall':'UTF-8'}</b> {$loanDetail->getStateReason()}<br>
            <b>{$text["downpayment"]|escape:'htmlall':'UTF-8'}</b> {round($amount, 2)|escape:'htmlall':'UTF-8'} {$loanDetail->getCurrency()|escape:'htmlall':'UTF-8'}<br>
            <br>
            <a id="refresh" class="btn btn-default" href="{$refreshLink|escape:'htmlall':'UTF-8'}">
                <i class="icon-refresh"></i>
                {$text["button"]|escape:'htmlall':'UTF-8'}
            </a>
        </div>
        <div class="col-md-6">
            <form action="{$cancelLink|escape:'htmlall':'UTF-8'}" method="post" id="cancelform">
                <h4>{l s='Cancel' mod='myloanconnector' }</h4>
                <select name="reason" type="text" class="form-control" >
                    <option value="APPLICATION_CANCELLED_CARRIER_CHANGED">
                        {l s='Order carrier changed'  mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_CART_CONTENT_CHANGED">
                        {l s='Cart content changed or customer returned from the cart' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_BY_CUSTOMER">
                        {l s='Customer cancelled order (in his profile or through customer center)' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_BY_ERP">
                        {l s='Cancelled by e-shop\'s back-office process (e.g. some items unavailable)' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_EXPIRED">
                        {l s='Application expired (too long waiting time for response from HC)' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_UNFINISHED">
                        {l s='Customer did not finish order' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_BY_ESHOP_RULES">
                        {l s='Internal e-shop rules breached (e.g. problem with customer\'s additional data)' mod='myloanconnector' }
                    </option>
                    <option value="APPLICATION_CANCELLED_OTHER">
                        {l s='Other reason - specified in customReason' mod='myloanconnector' }
                    </option>

                </select>
                <input name="message" type="text" class="form-control"  placeholder="{l s='Cancel reason' mod='myloanconnector' }"><br>

                <button class="btn btn-default" type="submit" form="cancelform">
                    <i class="icon-remove"></i>
                    {l s='Cancel' mod='myloanconnector' }
                </button>
            </div>
        </div>
    </div>
</div>
