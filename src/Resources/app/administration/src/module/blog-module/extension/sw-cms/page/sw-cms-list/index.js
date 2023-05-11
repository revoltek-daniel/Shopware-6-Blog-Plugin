const { Component } = Shopware;

Component.override('sw-cms-list', {
    methods: {
        createdComponent() {
            this.cmsPageTypeService.register({name: 'blog_detail', icon: 'egular-tag', title: 'sw-cms.sorting.labelSortByBlogPages'})

            this.$super('createdComponent');
        }
    },
});
