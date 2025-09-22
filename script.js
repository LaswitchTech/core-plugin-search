const SearchCapture = function(){

    // Clone the document body so we don't modify the live DOM
    const clone = document.body.cloneNode(true);

    // Remove script, style, and noscript tags
    clone.querySelectorAll('script, style, noscript').forEach(el => el.remove());

    // Return the HTML content
    return clone.innerHTML || clone.outerHTML || "";
}
const SearchFilter = function(){

    // Clone the document body so we don't modify the live DOM
    const clone = document.body.cloneNode(true);

    // Remove script, style, and noscript tags
    clone.querySelectorAll('script, style, noscript').forEach(el => el.remove());

    // Get text content
    let text = clone.innerText || clone.textContent || "";

    // Normalize whitespace: collapse multiple spaces/newlines into one space
    text = text.replace(/\s+/g, ' ').trim();

    return text;
}
const SearchHighlight = function(str, query){
    if (!query) return str;                    // nothing to do
    // 1) escape regex metacharacters in the query
    const esc   = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // 2) global / case‑insensitive search
    const regex = new RegExp(`(${esc})`, 'gi');
    // 3) wrap each hit
    return str.replace(regex, '<span class="text-bg-warning">$1</span>');
};
const SearchRenderer = function(query, hit){
    console.log(query, hit);

    const pathText       = `${hit.route.route}${hit.segments}`;
    const highlighted    = {
        label   : SearchHighlight(hit.route.label,   query),
        path    : SearchHighlight(pathText,          query),
        title   : SearchHighlight(hit.title,         query),
        excerpt : SearchHighlight(hit.excerpt,       query),
    };
    var scoring = '';
    if (DEV_MODE) {
        scoring = `<span class="text-muted ms-1">[${hit.score}]</span>`;
    }

    return `<div class="col card card-body user-select-none cursor-pointer" style="transition: all 0.3s ease-in-out;">
        <div class="d-flex">
            <i class="bi bi-${hit.route.icon} fs-2 me-2"></i>
            <div class="flex-grow-1">
                <h5 class="mb-0">${highlighted.label}</h5>
                <a class="small">${highlighted.path}</a>
            </div>
        </div>
        <h3 class="h5 my-2">${highlighted.title}${scoring}</h3>
        <p class="mb-0">
            <span class="text-muted">${hit.modified} - </span>${highlighted.excerpt}
        </p>
    </div>`;
}
const SearchIndex = function(){
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('query');
    if (query !== null) {
        return;
    }
    API.endpoint('/search/index').data({
        "title": document.title,
        "route": window.location.pathname,
        "segments": window.location.search,
        "locale": builder.Locale.current(),
        "origin": SearchCapture(),
        "content": SearchFilter(),
        "isPublic": PUBLIC,
    }).execute();
}

// Submit current index to the server
$(document).ready(function(){
    setTimeout(function(){
        // Check if the page is loaded
        if (document.readyState === 'complete') {
            SearchIndex();
        }
    }
    , 1000);
});
