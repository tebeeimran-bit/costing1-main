<div class="alert alert-warning" id="unpricedTopBanner"
        style="{{ (isset($trackingRevision) && $trackingRevision && isset($openUnpricedParts) && $openUnpricedParts->count() > 0) ? '' : 'display:none;' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3l-8.47-14.14a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
            <span id="unpricedTopBannerText">
                Terdapat {{ isset($openUnpricedParts) ? $openUnpricedParts->count() : 0 }} part yang belum memiliki harga pada versi dokumen ini.
            </span>
    </div>
