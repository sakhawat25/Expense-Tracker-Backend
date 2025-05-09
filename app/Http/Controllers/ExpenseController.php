<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue3-toastify'
import axios from 'axios'
import 'vue3-toastify/dist/index.css'

import Layout from '@/components/Layout.vue'
import InputBox from '@/components/InputBox.vue'
import FormDialogue from '@/components/FormDialogue.vue'

const router = useRouter()
const authStore = useAuthStore()

const isLoggingout = ref(false)
const isLoading = ref(false)
const showDialog = ref(false)
const expenses = ref(null)
const id = ref(null)

const formData = reactive({
  category: '',
  amount: '',
  date: '',
})

const validationErrors = reactive({
  category: null,
  amount: null,
  date: null,
})

const toggleSidebar = () => {
  const sidebar = document.getElementById('sidebar')
  const mainContent = document.getElementById('mainContent')
  sidebar.classList.toggle('-translate-x-full')
  mainContent.classList.toggle('ml-64')
}

const handleLogout = async () => {
  isLoggingout.value = true
  const token = localStorage.getItem('token')

  try {
    await axios.post(`${import.meta.env.VITE_API_BASE_URL}/logout`, {}, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    localStorage.removeItem('token')
    authStore.user = null
    router.push({ name: 'login' })
  } catch (error) {
    console.error(error.response)
  } finally {
    isLoggingout.value = false
  }
}

const handleAddExpense = async () => {
  isLoading.value = true
  clearErrors()

  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/expenses`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    clearForm()
    showSuccessAlert('Expense added successfully.')
    expenses.value = data.data.expenses
  } catch (error) {
    setValidationErrors(error)
  } finally {
    isLoading.value = false
  }
}

const handleEditExpense = async (expenseId) => {
  clearErrors()
  clearForm()

  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/expenses/${expenseId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    formData.amount = data.amount
    formData.category = data.category?.name || ''
    formData.date = data.date
    id.value = data.id
    showDialog.value = true
  } catch (error) {
    setValidationErrors(error)
  }
}

const handleUpdateExpense = async (expenseId) => {
  clearErrors()

  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.put(`${import.meta.env.VITE_API_BASE_URL}/expenses/${expenseId}`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    clearForm()
    showSuccessAlert('Expense updated successfully.')
    expenses.value = data.data.expenses
    showDialog.value = false
  } catch (error) {
    setValidationErrors(error)
  }
}

const clearForm = () => {
  Object.keys(formData).forEach((key) => (formData[key] = ''))
}

const clearErrors = () => {
  Object.keys(validationErrors).forEach((key) => (validationErrors[key] = null))
}

const setValidationErrors = (error) => {
  const errors = error?.response?.data?.errors || {}
  validationErrors.category = errors.category ?? null
  validationErrors.amount = errors.amount ?? null
  validationErrors.date = errors.date ?? null
}

const closeDialog = () => {
  clearForm()
  clearErrors()
  showDialog.value = false
}

const showSuccessAlert = (message) => {
  toast(message, {
    theme: 'colored',
    type: 'success',
    transition: 'slide',
    dangerouslyHTMLString: true,
  })
}

onMounted(async () => {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/expenses`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    expenses.value = data.data.expenses
  } catch (error) {
    console.error(error)
  }
})

onUnmounted(() => {
  clearForm()
  clearErrors()
})
</script>

<template>
  <Layout>
    <main id="mainContent" class="flex-1 p-6 transition-all w-full md:ml-0 relative">
      <header class="flex justify-between items-center bg-white p-4 shadow rounded-md">
        <button class="md:hidden px-4 py-2 bg-indigo-700 text-white rounded" @click="toggleSidebar">
          <i class="pi pi-align-justify"></i>
        </button>
        <h2 class="text-xl font-semibold">Expenses</h2>
        <button
          @click="handleLogout"
          :disabled="isLoggingout"
          :class="['px-4 py-2 text-white rounded transition', isLoggingout ? 'bg-red-400 cursor-not-allowed opacity-60' : 'bg-red-500 hover:bg-red-600']"
        >
          Logout
          <i v-if="isLoggingout" class="pi pi-spin pi-spinner-dotted"></i>
          <i v-else class="pi pi-sign-out"></i>
        </button>
      </header>

      <div class="mt-6 bg-white p-6 shadow rounded-md">
        <h3 class="text-lg font-semibold text-gray-700">Add New Expense</h3>
        <form @submit.prevent="handleAddExpense">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
            <InputBox v-model="formData.date" label="Date" type="date" :errorMessages="validationErrors.date" />

            <div>
              <label for="category" class="block text-sm text-gray-700">Category</label>
              <select v-model="formData.category" id="category" class="mt-1 px-4 py-2 w-full border rounded-md focus:ring focus:ring-indigo-700 focus:outline-none">
                <option value="Food">Food</option>
                <option value="Transport">Transport</option>
                <option value="Shopping">Shopping</option>
                <option value="Bills">Bills</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <InputBox v-model="formData.amount" label="Amount" type="number" placeholder="100" :errorMessages="validationErrors.amount" />
            </div>

            <div class="sm:col-span-2 flex justify-end mt-4">
              <button
                type="submit"
                :disabled="isLoading"
                :class="['relative w-2xs px-6 py-2 text-white rounded-md', isLoading ? 'bg-green-600 cursor-not-allowed opacity-60' : 'bg-green-600 hover:bg-green-700']"
              >
                Add Expense
                <i v-if="isLoading" class="pi pi-spin pi-spinner-dotted absolute top-3 right-4"></i>
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="mt-6 bg-white p-6 shadow rounded-md">
        <h3 class="text-lg font-semibold text-gray-700">Recent Expenses</h3>
        <table class="w-full mt-4 border-collapse">
          <thead>
            <tr class="bg-gray-200 text-gray-700">
              <th class="p-2 text-left">Date</th>
              <th class="p-2 text-left">Category</th>
              <th class="p-2 text-right">Amount</th>
              <th class="p-2 text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="expense in expenses?.data" :key="expense.id" class="border-b">
              <td class="p-2">{{ expense.date }}</td>
              <td class="p-2">{{ expense.category?.name ?? 'NA' }}</td>
              <td class="p-2 text-right text-red-600">- ${{ expense.amount }}</td>
              <td class="flex justify-center items-center gap-2 p-2">
                <button @click="() => handleEditExpense(expense.id)" title="Edit" class="px-2 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600">
                  <i class="pi pi-file-edit"></i>
                </button>
                <button title="Delete" class="px-2 py-1 bg-red-500 text-white rounded-md hover:bg-red-600">
                  <i class="pi pi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-6 flex justify-center">
        <nav>
          <ul class="flex space-x-4">
            <li v-for="(link, index) in expenses?.links" :key="index">
              <button v-html="link.label" :class="['px-4 py-2 rounded-md', link.active ? 'bg-indigo-700 text-white' : 'bg-gray-300 text-gray-700']"></button>
            </li>
          </ul>
        </nav>
      </div>

      <FormDialogue @close="closeDialog" :isOpen="showDialog" formId="update-expense">
        <form id="update-expense" @submit.prevent="handleUpdateExpense(id)">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
            <InputBox v-model="formData.date" label="Date" type="date" :errorMessages="validationErrors.date" />

            <div>
              <label for="category" class="block text-sm text-gray-700">Category</label>
              <select v-model="formData.category" id="category" class="mt-1 px-4 py-2 w-full border rounded-md focus:ring focus:ring-indigo-700 focus:outline-none">
                <option value="Food">Food</option>
                <option value="Transport">Transport</option>
                <option value="Shopping">Shopping</option>
                <option value="Bills">Bills</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <InputBox v-model="formData.amount" label="Amount" type="number" placeholder="100" :errorMessages="validationErrors.amount" />
            </div>
          </div>
        </form>
      </FormDialogue>
    </main>
  </Layout>
</template>
