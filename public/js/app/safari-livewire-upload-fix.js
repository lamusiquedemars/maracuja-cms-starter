(() => {
    const isWebKit = /AppleWebKit/i.test(navigator.userAgent)
        && !/(Chrome|Chromium|CriOS|Edg)/i.test(navigator.userAgent);

    if (!isWebKit || window.__maracujaSafariLivewireUploadFixInstalled) return;

    window.__maracujaSafariLivewireUploadFixInstalled = true;

    const open = XMLHttpRequest.prototype.open;
    const send = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, ...arguments_) {
        this.__maracujaRequestUrl = String(url);

        return open.call(this, method, url, ...arguments_);
    };

    XMLHttpRequest.prototype.send = function (body) {
        if (!this.__maracujaRequestUrl?.includes('/livewire/upload-file') || !(body instanceof FormData)) return send.call(this, body);

        const uploadBody = new FormData();

        body.forEach((value, key) => {
            if (!(value instanceof File)) return uploadBody.append(key, value);

            const file = value.slice(0, value.size, value.type);
            uploadBody.append(key, file, value.name);
        });

        return send.call(this, uploadBody);
    };
})();
