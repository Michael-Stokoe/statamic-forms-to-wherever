import { FieldtypeMixin as Fieldtype } from '@statamic/cms';

Statamic.$components.register('form_connectors-fieldtype', {
    mixins: [Fieldtype],
    
    template: `
        <replicator-fieldtype
            :config="replicatorConfig"
            :value="value"
            :meta="meta"
            :name="name"
            :handle="handle"
            @update:value="update($event)"
        />
    `,
    
    computed: {
        replicatorConfig() {
            return {
                ...this.config,
                sets: this.meta.sets || {},
                type: 'replicator',
            };
        }
    }
});
