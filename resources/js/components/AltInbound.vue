<script setup>
import { Header, Heading, Subheading, PublishContainer, Button, Switch, Card, Input, Pagination } from '@statamic/cms/ui';
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
});

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

const itemsSliced = computed(() => {
    let temp = props.items;

    if (search.value?.length > 0) {
        temp = temp.filter(item => {
            // Convert all values to string and lower case for case-insensitive comparison
            let tempArr = Object.values(item).map(value => value?.toString().toLowerCase());
            // Check if any value includes the search string
            return tempArr.some(value => value?.includes(search.value.toLowerCase()));
        });
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

function deleteRedirect(ip, id) {
    if (confirm('Are you sure you want to delete this block?')) {
        router.post(cp_url('alt-design/alt-inbound/delete'), {ip, id}, {
            preserveState: "errors",
            preserveScroll: true, 
            onSuccess: () => {
                Statamic.$toast.success("Block deleted successfully!")
                setPage(currentPage.value);
            }
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
        }
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
        }
    });
}

function viewChanged() {
    router.post(cp_url('alt-design/alt-inbound/custom-view'), {'custom-view': customViewValue.value}, {
        preserveState: "errors",
        preserveScroll: true, 
        onSuccess: () => {
            Statamic.$toast.success("View updated successfully!")
        }
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

        <Card class="overflow-hidden p-0">
            <div class="mt-4 pb-2 px-4 flex items-center">
                <div class="w-1/3 flex items-center gap-3">
                    <Switch size="lg" v-model="blacklistValue" v-on:update:model-value="blacklistChange()" />
                    <div class="ml-4 text-sm">Whitelist / Blacklist</div>
                </div>
                <Input type="text" class="input-text w-1/3" v-model="search" placeholder="Search" />
            </div>
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
                        <tr v-for="item in itemsSliced.data" :key="item.id" style="width : 100%; overflow: clip">
                            <td>
                                {{ item.ip }}
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
            <Card class="w-full xl:w-1/3 card overflow-hidden p-0 mb-4 mt-4 mr-4 px-4 py-4">
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
