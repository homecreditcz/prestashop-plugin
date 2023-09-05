<!--myloanconnector header-->
{addJsDef actionUrl={$actionUrl|escape:'quotes'}}
{addJsDef apiKey={$apiKey|escape:'quotes'}}
{addJsDef productSetCode={$productSetCode|escape:'htmlall':'UTF-8'}}
{addJsDef cartOrderTotal={$cartOrderTotal|escape:'htmlall':'UTF-8'}}
{addJsDef calcUrl={$calcUrl|escape:'quotes'}}
{addJsDef calcPostUrl={$calcPostUrl|escape:'quotes'}}
{addJsDef isCertified=isset($isCertified)|boolval}
{addJsDef minimalPrice={json_encode($minimalPrice)}}
{addJsDef productId={$productId|escape:'htmlall':'UTF-8'}}
<!--myloanconnector header end-->
