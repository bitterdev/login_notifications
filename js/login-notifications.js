const fpPromise = import(CCM_APPLICATION_URL + "/packages/login_notifications/js/fingerprintjs.js")
    .then(
        FingerprintJS => FingerprintJS.load()
    )

fpPromise
    .then(fp => fp.get())
    .then(result => {
        const visitorId = result.visitorId;
        document.cookie = `fingerprint=${visitorId}; path=/; max-age=${30 * 24 * 60 * 60}; secure; samesite=strict`;
    });