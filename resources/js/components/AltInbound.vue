<script setup>
import { Alert, Badge, Header, Heading, Subheading, PublishContainer, Button, Switch, Card, Input, Pagination } from '@statamic/cms/ui';
import { Pipeline, BeforeSaveHooks, Request, AfterSaveHooks } from '@statamic/cms/save-pipeline'; 
import { router } from '@statamic/cms/inertia'
import { computed, ref, watch } from 'vue'; 

const props = defineProps({
    blueprint: Array,
    initialMeta: Array,
    initialValues: Array,
    data: Array,
    items: Array,
    blacklist: Boolean,
    customView: String,
    canManageSettings: Boolean,
    modeSetByEnvironment: Boolean,
    warnings: Array,
});

const problemLabels = {
    'invalid': 'Not a single IP address, so it matches no visitor',
    'non-standard': 'Not saved in standard form, so it may not match. Remove it and add it again',
};

const perPage = 10;
const currentPage = ref(1);
const selectedFile = ref(null);
const search = ref('');
const values = ref({...props.initialValues});
const meta = ref(props.initialMeta);
const errors = ref({});
const saving = ref(false);
const container = ref('container');
const blacklistValue = ref(props.blacklist);
const customViewValue = ref(props.customView);

const lastPage = computed(() => {
    return Math.ceil(itemsSliced.value.total / perPage);
});

const modeName = computed(() => blacklistValue.value ? 'Block-list mode' : 'Allow-list mode');

const problemCount = computed(() => props.items.filter(item => item.problem).length);

const itemsSliced = computed(() => {
    let temp = props.items;

    if (search.value?.length > 0) {
        const term = search.value.toLowerCase();
        temp = temp.filter(item => [item.ip, item.id].some(value => value?.toString().toLowerCase().includes(term)));
    }

    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;

    return {
        total: temp.length,
        data: temp.slice(start, end)
    };
});

function setPage(page) {
    // If the page we were looking at has now been removed
    if(page > lastPage.value) {
        page = lastPage.value;
    }
    currentPage.value = page
}

function showFirstError(errors) {
    Statamic.$toast.error(Object.values(errors)[0]);
}

function deleteRedirect(ip, id) {
    if (confirm('Are you sure you want to delete this block?')) {
        router.post(cp_url('alt-design/alt-inbound/delete'), {ip, id}, {
            preserveState: "errors",
            preserveScroll: true, 
            onSuccess: () => {
                Statamic.$toast.success("Block deleted successfully!")
                setPage(currentPage.value);
            },
            onError: showFirstError,
        });
    }
}

function importFromCSV() {
    if (!selectedFile.value) {
        alert("You haven't attached a CSV file!");
        return;
    }

    router.post(cp_url('alt-design/alt-inbound/import'), {file: selectedFile.value}, {
        preserveState: "errors",
        preserveScroll: true, 
        onSuccess: () => {
            Statamic.$toast.success("CSV imported successfully!")
            setPage(currentPage.value);
            selectedFile.value = null;
        },
        onError: showFirstError,
    });
}

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new BeforeSaveHooks('alt-inbound'),
            new Request(cp_url('/alt-design/alt-inbound'), 'POST'),
            new AfterSaveHooks('alt-inbound'),
        ])
        .then(() => {
            values.value = {...props.initialValues}
            Statamic.$toast.success("Block added successfully!")
            router.reload()
        });
}

function blacklistChange() {
    router.post(cp_url('alt-design/alt-inbound/blacklist'), {blacklist: blacklistValue.value}, {
        preserveState: "errors",
        preserveScroll: true, 
        onSuccess: () => {
            Statamic.$toast.success("Updated successfully!")
            router.reload({ only: ['warnings', 'blacklist'] })
        },
        onError: (errors) => {
            showFirstError(errors);
            blacklistValue.value = props.blacklist;
        },
    });
}

function viewChanged() {
    router.post(cp_url('alt-design/alt-inbound/custom-view'), {'custom-view': customViewValue.value}, {
        preserveState: "errors",
        preserveScroll: true, 
        onSuccess: () => {
            Statamic.$toast.success("View updated successfully!")
        },
        onError: (errors) => {
            showFirstError(errors);
            customViewValue.value = props.customView;
        },
    });
}

watch(search, () => {
    setPage(1)
});
</script>

<template>
    <div id="alt-inbound">
        <Header :title="title">
            <template #title>
                <div>
                    Alt Inbound
                    <div class="text-sm">{{ instructions }}</div>
                </div>
            </template>
            <Button text="Save" variant="primary" :disabled="saving" @click="save" /> 
        </Header>

        <PublishContainer ref="container" :blueprint="blueprint" :meta="meta" v-model="values" :errors="errors" />

        <Alert v-for="warning in warnings" :key="warning" variant="warning" :text="warning" class="mb-4" />

        <Card class="overflow-hidden p-0">
            <div class="mt-4 pb-2 px-4 flex items-center">
                <div class="w-1/3 flex items-center gap-3">
                    <Switch v-if="canManageSettings" size="lg" v-model="blacklistValue" :disabled="modeSetByEnvironment" aria-label="Block-list mode" v-on:update:model-value="blacklistChange()" />
                    <div class="text-sm">
                        <div class="font-medium">{{ modeName }}</div>
                        <div v-if="modeSetByEnvironment" class="text-gray-600">Set by ALT_INBOUND_MODE</div>
                    </div>
                </div>
                <Input type="text" class="input-text w-1/3" v-model="search" placeholder="Search" />
            </div>
            <p v-if="problemCount" class="px-4 pb-2 text-sm">
                Entries saved by an earlier version that may not match visitors: {{ problemCount }}. These are listed first.
            </p>
            <div class="px-2">
                <table data-size="sm" tabindex="0" class="data-table" style="table-layout: fixed">
                    <thead>
                        <tr>
                            <th class="group from-column sortable-column" style="width:33%">
                                <span>IP Address</span>
                            </th>
                            <th class="actions-column" style="width:13.4%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in itemsSliced.data" :key="`${item.id}-${item.ip}`" style="width : 100%; overflow: clip">
                            <td>
                                {{ item.ip }}
                                <div v-if="item.problem" class="mt-1">
                                    <Badge color="amber" size="sm" :text="problemLabels[item.problem]" />
                                </div>
                            </td>
                            <td>
                                <Button icon="trash" size="sm" @click="deleteRedirect(item.ip, item.id)" text="Remove" variant="danger" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :resource-meta="{
                current_page: currentPage,
                last_page: lastPage,
                total: itemsSliced.total
            }" :show-totals="false" :show-per-page-selector="false" @page-selected="setPage" />
        </Card>

        <div class="flex justify-between flex-wrap">
            <Card v-if="canManageSettings" class="w-full xl:w-1/3 card overflow-hidden p-0 mb-4 mt-4 mr-4 px-4 py-4">
                <header>
                    <Heading>Custom Blocked View</Heading>
                    <Subheading>Pop your template path here to use a custom view. For example: <code>templates.blocked</code> or <code>templates/blocked</code></Subheading>
                </header>
                <Input type="text" class="input-text w-1/3" @change="viewChanged()" v-model="customViewValue" placeholder="Custom View Template" />
            </Card>

            <Card class="w-full xl:w-1/3 card overflow-hidden p-0 mb-4 mt-4 mr-4 px-4 py-4">
                <header>
                    <Heading>CSV Export</Heading>
                    <Subheading>Exports CSV of all blocks, use this format on import.</Subheading>
                </header>
                <a class="btn-primary" :href="cp_url('/alt-design/alt-inbound/export')" download>
                    <Button text="Export CSV"/>
                </a>
            </Card>

            <Card class="w-full xl:w-1/3 card overflow-hidden p-0 mb-4 mt-4 ml-4 px-4 py-4">
                <header>
                    <Heading>CSV Import</Heading>
                    <Subheading>Import CSV for Blocks, use the export format on import.</Subheading>
                </header>

                <div class="flex justify-between items-center">
                    <Input type="file" @input="selectedFile = $event.target.files[0]" />
                    <Button @click="importFromCSV()" text="Import" />
                </div>
            </Card>
        </div>
    </div>
</template>

<style scoped></style>
