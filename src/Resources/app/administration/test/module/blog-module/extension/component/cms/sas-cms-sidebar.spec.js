import { shallowMount, createLocalVue } from '@vue/test-utils';
import 'src/app/component/sidebar/sw-sidebar';
import 'src/app/component/sidebar/sw-sidebar-item';
import 'src/module/sw-cms/mixin/sw-cms-state.mixin';
import 'src/module/sw-cms/component/sw-cms-sidebar';
import 'src/app/component/base/sw-button';

import 'plugin-admin/src/module/blog-module/extension/component/cms/sas-cms-sidebar';
import 'plugin-admin/src/module/blog-module/extension/component/form/sas-text-field';

function createWrapper(customOptions = {}) {
    const localVue = createLocalVue();
    localVue.directive('draggable', {});
    localVue.directive('droppable', {});
    localVue.directive('tooltip', {
        bind(el, binding) {
            el.setAttribute('tooltip-message', binding.value.message);
        },
        inserted(el, binding) {
            el.setAttribute('tooltip-message', binding.value.message);
        },
        update(el, binding) {
            el.setAttribute('tooltip-message', binding.value.message);
        },
    });

    const spyGetEntityNameOne = jest.fn(() => 'sas_blog_entries');

    localStorage.clear();

    const options = {
        localVue,
        propsData: {
            page: {
                sections: [],
            },
            blog: {
                title: 'blog title',
                slug: 'blog-slug',
                getEntityName: spyGetEntityNameOne,
            },
        },
        stubs: {
            'sw-sidebar-item': Shopware.Component.build('sw-sidebar-item'),
            'sw-sidebar': Shopware.Component.build('sw-sidebar'),
            'sw-button': Shopware.Component.build('sw-button'),
            'sw-sidebar-collapse': true,
            'sw-text-field': true,
            'sw-select-field': true,
            'sw-cms-block-config': true,
            'sw-cms-block-layout-config': true,
            'sw-cms-section-config': true,
            'sw-context-button': true,
            'sw-context-menu-item': true,
            'sw-cms-sidebar-nav-element': true,
            'sw-entity-single-select': true,
            'sw-modal': true,
            'sw-container': true,
            'sw-checkbox-field': true,
            'sw-upload-listener': true,
            'sw-media-compact-upload-v2': true,
            'sw-switch-field': true,
            'sw-datepicker': true,
            'sas-text-field': true,
            'sas-textarea-field': true,
            'sas-blog-category-tree-field': true,
            'sw-icon': true,
            'sw-sidebar-navigation-item': true,
        },
        provide: {
            repositoryFactory: {
                create: () => ({
                    create: () => ({ id: null, slots: [] }),
                    save: () => {},
                }),
            },
            cmsService: {
                getCmsBlockRegistry: () => ({
                    'foo-bar': {},
                }),
            },
            systemConfigApiService: {
                getValues: () => Promise.resolve('MeSasBlogModule.config')
            }
        },
    };

    return shallowMount(Shopware.Component.build('sas-cms-sidebar'), {
        ...options,
        ...customOptions,
    });
}

describe('module/sw-cms/component/sw-cms-sidebar', () => {
    beforeAll(() => {
        Shopware.State.registerModule('cmsPageState', {
            namespaced: true,
            state: {
                isSystemDefaultLanguage: true,
            },
        });
    });

    it('should be a Vue.js component', async () => {
        const wrapper = createWrapper();

        expect(wrapper.vm).toBeTruthy();
    });

    it('should have blog detail tab', async () => {
        const wrapper = createWrapper();
        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$refs.blogDetailSidebar).toBeTruthy();
    });
});
