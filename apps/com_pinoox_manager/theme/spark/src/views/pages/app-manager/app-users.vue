<template>
  <PageSection title="کاربران">
    <div v-if="users.length" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-800">
        <thead>
        <tr>
          <th class="px-4 py-2 text-right">نام</th>
          <th class="px-4 py-2 text-right">ایمیل</th>
          <th class="px-4 py-2 text-right">وضعیت</th>
          <th class="px-4 py-2 text-right">تاریخ ثبت</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="user in users" :key="user.email">
          <td class="px-4 py-2">{{ user.full_name }}</td>
          <td class="px-4 py-2 ltr">{{ user.email }}</td>
          <td class="px-4 py-2">{{ user.status_fa }}</td>
          <td class="px-4 py-2">{{ user.register_date_fa }}</td>
        </tr>
        </tbody>
      </table>
    </div>
    <PageEmpty v-else title="کاربری یافت نشد"/>
  </PageSection>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { userAPI } from "@api/user.js";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";

const props = defineProps({ packageName: String });
const users = ref([]);

onMounted(async () => {
  const response = await userAPI.getUsers(props.packageName);
  users.value = unwrapResponse(response) ?? [];
});
</script>
