<article id="layout"></article>
<!-- <article id="layout" class="bg-danger p-5"></article> -->
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('searchResults',"#layout",{query: '<?= $this->Request->getParams('GET', 'query') ?>'});
        });
    })();
</script>
