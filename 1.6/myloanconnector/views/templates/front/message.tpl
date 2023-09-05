{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{if $show_message}

    <div class="bootstrap">
        <div class="alert alert-{$type|escape:'htmlall':'UTF-8'}">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {$show_message|escape:'htmlall':'UTF-8'}
        </div>
    </div>

{/if}