{{-- Page-load preloader: spinning film reel + logo. Faded out by main.js on window "load". --}}
<div class="preloader" id="preloader" role="status" aria-label="Loading Point of View">
    <div class="reel" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)
            <span class="reel__hole" style="--i: {{ $i }}"></span>
        @endfor
    </div>
    <div class="preloader__brand" aria-hidden="true">Point <em>of</em> View</div>
    <div class="preloader__bar" aria-hidden="true"><span></span></div>
</div>
