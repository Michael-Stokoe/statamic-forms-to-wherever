<template>
    <replicator-fieldtype
        :config="replicatorConfig"
        :value="value"
        :meta="meta"
        :name="name"
        :handle="handle"
        @update:value="update($event)"
    />
</template>

<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';

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
