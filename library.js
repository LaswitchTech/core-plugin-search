builder.add('layouts','searchResults', class extends builder.ComponentClass {

    _init(){
        this._properties = {
            class: {
                component: null,
            },
            query: null,
            interval: 15000,
            autoStart: true,
            callback: {},
        };
        this._data = {};
    }

    _create(){

        // Set Self
        const self = this;

        // Create Component
        this._component = $(document.createElement('div')).attr({
            'id': 'search' + this._id,
            'class': 'search-results',
        });
        this._component.id = this._component.attr('id');

        // Add Class
        if(this._properties.class.component){
            this._component.addClass(this._properties.class.component);
        }

        // Perform Search
        API.endpoint('/search/query?query='+this._properties.query).execute(function(response){
            for(const [key, index] of Object.entries(response.results)){
                self.add(index);
            }
        });
    }

    add(index){
        const pathText       = `${index.route.route}${index.segments}`;
        const highlighted    = {
            label   : this.highlight(index.route.label),
            path    : this.highlight(pathText),
            title   : this.highlight(index.title),
            excerpt : this.highlight(index.excerpt),
        };
        var scoring = '';
        if (DEV_MODE) {
            scoring = `<span class="text-muted ms-1">[${index.score}]</span>`;
        }

        $(`<div class="search-results-item">
            <div class="search-results-item-header">
                <i class="bi bi-${index.route.icon}"></i>
                <div class="search-results-item-header-text">
                    <h5>${highlighted.label}</h5>
                    <span>${highlighted.path}</span>
                </div>
            </div>
            <h3 class="h5 my-2">${highlighted.title}${scoring}</h3>
            <p class="mb-0">
                <span class="text-muted">${index.modified} - </span>${highlighted.excerpt}
            </p>
        </div>`).appendTo(this._component).click(function() {

            // Redirect to the index
            window.location.href = `${index.route.route}${index.segments}`;
        });

        return this;
    }

    highlight(str){
        if (!this._properties.query) return str;
        // 1) escape regex metacharacters in the query
        const esc   = this._properties.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        // 2) global / case‑insensitive search
        const regex = new RegExp(`(${esc})`, 'gi');
        // 3) wrap each hit
        return str.replace(regex, '<span class="text-bg-warning">$1</span>');
    }
});
