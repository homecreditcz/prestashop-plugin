{**
*  @author HN Consulting Brno s.r.o
*  @copyright  2019-*
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<div id="hc-calc-container" class="row">
    <div id="hc-calc-modal" class="hc-modal" role="dialog" style="display: none; z-index: 999999;">
        <div class="hc-modal__dialog">
            <div class="hc-modal__content">
                <div id="hc-modal-header" class="hc-modal__header">
                    <span id="hc-close-button" class="hc-modal__close"
                       onclick="document.getElementById('hc-calc-modal').style.display = 'none'"></span>
                    <div class="hc-modal__logo">
                        <img src="{$hcLogo|escape:'quotes'}"
                             alt="logo">
                    </div>
                    <div class="hc-modal__title"></div>
                </div>
                <div id="hc-calculator-wrapper" class="hc-modal__body"></div>
            </div>
        </div>
</div>
</div>