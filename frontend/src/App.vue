<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from './api'

const token = ref(localStorage.getItem('auth_token'))
const books = ref([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingBook = ref(null)
const loginForm = reactive({ email: 'admin@buku.test', password: 'password' })
const bookForm = reactive({ title: '', author: '', published_year: '' })

const isLoggedIn = computed(() => Boolean(token.value))
const formTitle = computed(() => editingBook.value ? 'Ubah buku' : 'Tambah buku')

function getErrorMessage(error) {
  return error.response?.data?.message
    || Object.values(error.response?.data?.errors || {}).flat()[0]
    || 'Terjadi kesalahan. Silakan coba lagi.'
}

function clearMessages() {
  errorMessage.value = ''
  successMessage.value = ''
}

async function login() {
  clearMessages()

  try {
    const response = await api.post('/login', loginForm)
    token.value = response.data.token
    localStorage.setItem('auth_token', token.value)
    successMessage.value = 'Login berhasil.'
    await loadBooks()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  }
}

async function logout() {
  clearMessages()

  try {
    await api.post('/logout')
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
    return
  }

  localStorage.removeItem('auth_token')
  token.value = null
  books.value = []
  successMessage.value = 'Logout berhasil.'
}

async function loadBooks() {
  loading.value = true
  clearMessages()

  try {
    const response = await api.get('/books')
    books.value = response.data.data
  } catch (error) {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      token.value = null
    }
    errorMessage.value = getErrorMessage(error)
  } finally {
    loading.value = false
  }
}

function startEdit(book) {
  editingBook.value = book.id
  bookForm.title = book.title
  bookForm.author = book.author
  bookForm.published_year = book.published_year
  clearMessages()
}

function resetForm() {
  editingBook.value = null
  bookForm.title = ''
  bookForm.author = ''
  bookForm.published_year = ''
}

async function saveBook() {
  saving.value = true
  clearMessages()
  const payload = {
    title: bookForm.title,
    author: bookForm.author,
    published_year: Number(bookForm.published_year),
  }

  try {
    if (editingBook.value) {
      await api.put(`/books/${editingBook.value}`, payload)
      successMessage.value = 'Buku berhasil diubah.'
    } else {
      await api.post('/books', payload)
      successMessage.value = 'Buku berhasil ditambahkan.'
    }
    resetForm()
    await loadBooks()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    saving.value = false
  }
}

async function deleteBook(book) {
  if (!window.confirm(`Hapus buku "${book.title}"?`)) return

  clearMessages()

  try {
    await api.delete(`/books/${book.id}`)
    successMessage.value = 'Buku berhasil dihapus.'
    await loadBooks()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  }
}

onMounted(() => {
  if (isLoggedIn.value) loadBooks()
})
</script>

<template>
  <main class="page-shell">
    <section v-if="!isLoggedIn" class="card login-card">
      <div class="brand-mark">B</div>
      <p class="eyebrow">PERPUSTAKAAN DIGITAL</p>
      <h1>Selamat datang kembali</h1>
      <p class="muted">Masuk untuk mengelola koleksi buku.</p>
      <form @submit.prevent="login">
        <label>
          Email
          <input v-model="loginForm.email" type="email" required />
        </label>
        <label>
          Password
          <input v-model="loginForm.password" type="password" required />
        </label>
        <button class="primary-button" type="submit">Masuk</button>
      </form>
      <p v-if="errorMessage" class="alert error">{{ errorMessage }}</p>
      <p v-if="successMessage" class="alert success">{{ successMessage }}</p>
    </section>

    <section v-else class="dashboard">
      <header class="topbar">
        <div>
          <p class="eyebrow">PERPUSTAKAAN DIGITAL</p>
          <h1>Koleksi Buku</h1>
        </div>
        <button class="secondary-button" @click="logout">Keluar</button>
      </header>

      <div class="content-grid">
        <section class="card">
          <div class="section-heading">
            <div>
              <p class="eyebrow">KOLEKSI</p>
              <h2>Daftar buku</h2>
            </div>
            <span class="count-badge">{{ books.length }} buku</span>
          </div>
          <p v-if="loading" class="muted">Memuat data...</p>
          <div v-else-if="books.length === 0" class="empty-state">Belum ada buku.</div>
          <div v-else class="book-list">
            <article v-for="book in books" :key="book.id" class="book-row">
              <div class="book-icon">▤</div>
              <div class="book-info">
                <strong>{{ book.title }}</strong>
                <span>{{ book.author }} · {{ book.published_year }}</span>
              </div>
              <div class="row-actions">
                <button class="icon-button" title="Ubah buku" @click="startEdit(book)">Ubah</button>
                <button class="icon-button danger" title="Hapus buku" @click="deleteBook(book)">Hapus</button>
              </div>
            </article>
          </div>
        </section>

        <section class="card form-card">
          <div class="section-heading">
            <div>
              <p class="eyebrow">FORMULIR</p>
              <h2>{{ formTitle }}</h2>
            </div>
          </div>
          <form @submit.prevent="saveBook">
            <label>
              Judul buku
              <input v-model="bookForm.title" type="text" placeholder="Contoh: Laskar Pelangi" required />
            </label>
            <label>
              Penulis
              <input v-model="bookForm.author" type="text" placeholder="Nama penulis" required />
            </label>
            <label>
              Tahun terbit
              <input v-model="bookForm.published_year" type="number" min="1000" max="2100" required />
            </label>
            <div class="form-actions">
              <button class="primary-button" type="submit" :disabled="saving">
                {{ saving ? 'Menyimpan...' : editingBook ? 'Simpan perubahan' : 'Tambah buku' }}
              </button>
              <button v-if="editingBook" class="secondary-button" type="button" @click="resetForm">Batal</button>
            </div>
          </form>
        </section>
      </div>
      <p v-if="errorMessage" class="alert error">{{ errorMessage }}</p>
      <p v-if="successMessage" class="alert success">{{ successMessage }}</p>
    </section>
  </main>
</template>
