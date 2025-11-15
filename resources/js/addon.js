Statamic.component('form_connectors-fieldtype', {
    mixins: [Fieldtype],
    
    template: `
        <replicator-fieldtype
            :config="replicatorConfig"
            :value="value"
            :meta="meta"
            :name="name"
            :handle="handle"
            @input="$emit('input', $event)"
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
