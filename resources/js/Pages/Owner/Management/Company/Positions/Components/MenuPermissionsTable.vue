<script setup>
import { computed } from "vue";
import Checkbox from "@/Components/UI/Checkbox.vue";
import { POSITION_MENU_PERMISSIONS } from "@/data/positionPermissions";

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue"]);

const permissions = computed({
    get: () => props.modelValue || {},
    set: (val) => emit("update:modelValue", val),
});

const updateAction = (menuKey, action, checked) => {
    if (props.readonly) return;
    const current = { ...(permissions.value || {}) };
    current[menuKey] = {
        ...(current[menuKey] || { view: false, create: false, edit: false, delete: false }),
        [action]: checked,
    };
    emit("update:modelValue", current);
};
</script>

<template>
    <div class="space-y-3">
        <!-- Section Title & Description -->
        <div>
            <h3 class="text-xs font-semibold text-slate-700">
                Hak Akses Menu
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Pilih menu dan batasan aksi yang dapat diakses oleh jabatan ini.
            </p>
        </div>

        <!-- Table Matrix Container -->
        <div class="border border-slate-200/80 rounded-xl overflow-hidden shadow-2xs bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                            <th class="py-3 px-4 min-w-[280px]">
                                Menu
                            </th>
                            <th class="py-3 px-3 text-center w-24">
                                View
                            </th>
                            <th class="py-3 px-3 text-center w-24">
                                Create
                            </th>
                            <th class="py-3 px-3 text-center w-24">
                                Edit
                            </th>
                            <th class="py-3 px-3 text-center w-24">
                                Delete
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr
                            v-for="item in POSITION_MENU_PERMISSIONS"
                            :key="item.key"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <!-- Menu Title & Description -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800 leading-snug">
                                    {{ item.title }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    {{ item.desc }}
                                </div>
                            </td>

                            <!-- View Action -->
                            <td class="py-3 px-3 text-center align-middle">
                                <div class="flex items-center justify-center">
                                    <Checkbox
                                        :id="`perm-${item.key}-view`"
                                        :modelValue="Boolean(permissions[item.key]?.view)"
                                        :disabled="readonly || item.disabled.view"
                                        @update:modelValue="val => updateAction(item.key, 'view', val)"
                                    />
                                </div>
                            </td>

                            <!-- Create Action -->
                            <td class="py-3 px-3 text-center align-middle">
                                <div class="flex items-center justify-center">
                                    <Checkbox
                                        :id="`perm-${item.key}-create`"
                                        :modelValue="Boolean(permissions[item.key]?.create)"
                                        :disabled="readonly || item.disabled.create"
                                        @update:modelValue="val => updateAction(item.key, 'create', val)"
                                    />
                                </div>
                            </td>

                            <!-- Edit Action -->
                            <td class="py-3 px-3 text-center align-middle">
                                <div class="flex items-center justify-center">
                                    <Checkbox
                                        :id="`perm-${item.key}-edit`"
                                        :modelValue="Boolean(permissions[item.key]?.edit)"
                                        :disabled="readonly || item.disabled.edit"
                                        @update:modelValue="val => updateAction(item.key, 'edit', val)"
                                    />
                                </div>
                            </td>

                            <!-- Delete Action -->
                            <td class="py-3 px-3 text-center align-middle">
                                <div class="flex items-center justify-center">
                                    <Checkbox
                                        :id="`perm-${item.key}-delete`"
                                        :modelValue="Boolean(permissions[item.key]?.delete)"
                                        :disabled="readonly || item.disabled.delete"
                                        @update:modelValue="val => updateAction(item.key, 'delete', val)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p v-if="error" class="text-xs text-rose-500 mt-1">
            {{ error }}
        </p>
    </div>
</template>
