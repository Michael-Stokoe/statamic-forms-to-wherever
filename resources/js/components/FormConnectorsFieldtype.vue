<template>
    <replicator-fieldtype
        :config="replicatorConfig"
        :value="value"
        :meta="meta"
        :name="name"
        :handle="handle"
        @input="$emit('input', $event)"
    />
</template>

<script>
export default {
    mixins: [Fieldtype],
    
    computed: {
        replicatorConfig() {
            return {
                ...this.config,
                sets: this.meta.sets || {},
                type: 'replicator',
            };
        }
    }
};
</script>
