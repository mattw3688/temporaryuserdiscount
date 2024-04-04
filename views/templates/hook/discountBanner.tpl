{if $showDiscountBanner and $bannerContent and $discountCode}
  <div id="discountBanner" style="background-color: {$bgColour}; color: {$textColour}; ">
    <div class="banner-content">
        {$bannerContent|escape:'html'}
      <div class="banner-timer">
        <p>Expires in <span data-expiry="{$discountExpiry}" id="countdownTimer"></span></p>
      </div>
    </div>
  </div>
{/if}
