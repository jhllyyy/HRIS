/*
 Project: Library Management System
 Authors: [Mark Yien Molina, Justine Lee Arcilla, Renz Kylle Lara, Anghel Lagrimas
            Jhon Lloyd B. Barela], [Student ID(s)]
 Date: [Submission Date]
 Description: GUI-based patient queue system using .
*/
class LibraryDatabase {
    constructor() {
        this.books = new Map();
        this.members = new Map();
        this.bookList = [];
        this.memberList = [];
        this.loadFromStorage();
    }

    saveToStorage() {
        localStorage.setItem('library_books', JSON.stringify(this.bookList));
        localStorage.setItem('library_members', JSON.stringify(this.memberList));
    }

    loadFromStorage() {
        try {
            const books = JSON.parse(localStorage.getItem('library_books') || '[]');
            const members = JSON.parse(localStorage.getItem('library_members') || '[]');
            this.bookList = books || [];
            this.memberList = members || [];
            this.books = new Map(this.bookList.map(b => [b.bookId, b]));
            this.members = new Map(this.memberList.map(m => [m.memberId, m]));
        } catch (e) {
            this.bookList = [];
            this.memberList = [];
            this.books = new Map();
            this.members = new Map();
        }
    }

    // Book operations
    addBook(bookId, title, author) {
        if (!bookId || !title || !author) return { success: false, message: 'All fields are required.' };
        if (this.books.has(bookId)) return { success: false, message: 'Book ID already exists!' };

        const book = { bookId, title, author, status: 'Available', dateAdded: new Date().toLocaleString() };
        this.books.set(bookId, book);
        this.bookList.push(book);
        this.saveToStorage();
        return { success: true, message: 'Book added successfully!', data: book };
    }

    getBook(bookId) { return this.books.get(bookId); }
    getAllBooks() { return [...this.bookList]; }

    deleteBook(bookId) {
        if (!this.books.has(bookId)) return { success: false, message: 'Book not found!' };
        this.books.delete(bookId);
        this.bookList = this.bookList.filter(b => b.bookId !== bookId);
        this.saveToStorage();
        return { success: true, message: 'Book deleted successfully!' };
    }

    updateBookStatus(bookId, status) {
        const book = this.books.get(bookId);
        if (!book) return { success: false, message: 'Book not found!' };
        book.status = status;
        // ensure bookList refers to same object, but reassign to ensure latest ordering
        this.bookList = this.bookList.map(b => (b.bookId === bookId ? book : b));
        this.saveToStorage();
        return { success: true, message: 'Book status updated!', data: book };
    }

    // Member operations
    addMember(memberId, name, contact) {
        if (!memberId || !name || !contact) 
            return { success: false, message: 'All fields are required.' };
        if (this.members.has(memberId)) 
            return { success: false, message: 'Member ID already exists!' };
        const member = { memberId, name, contact, dateRegistered: new Date().toLocaleString() };
        this.members.set(memberId, member);
        this.memberList.push(member);
        this.saveToStorage();
        return { success: true, message: 'Member added successfully!', data: member };
    }

    getMember(memberId) { return this.members.get(memberId); }
    getAllMembers() { return [...this.memberList]; }

    deleteMember(memberId) {
        if (!this.members.has(memberId)) return { success: false, message: 'Member not found!' };
        this.members.delete(memberId);
        this.memberList = this.memberList.filter(m => m.memberId !== memberId);
        this.saveToStorage();
        return { success: true, message: 'Member deleted successfully!' };
    }

    // Stats & sorting
    getStatistics() {
        const total = this.bookList.length;
        const available = this.bookList.filter(b => b.status === 'Available').length;
        const borrowed = this.bookList.filter(b => b.status === 'Borrowed').length;
        const totalMembers = this.memberList.length;
        return { total, available, borrowed, totalMembers };
    }

    sortBooks(criteria) {
        const sorted = [...this.bookList];
        switch (criteria) {
            case 'id': return sorted.sort((a,b)=>a.bookId.localeCompare(b.bookId));
            case 'title': return sorted.sort((a,b)=>a.title.localeCompare(b.title));
            case 'author': return sorted.sort((a,b)=>a.author.localeCompare(b.author));
            case 'status': return sorted.sort((a,b)=>a.status.localeCompare(b.status));
            default: return sorted;
        }
    }
}

const database = new LibraryDatabase();

// UI helpers
function showAlert(message, type='success'){
    const alertBox = document.getElementById('alertBox');
    if (!alertBox) return;
    alertBox.className = `alert alert-${type} show`;
    alertBox.textContent = message;
    clearTimeout(showAlert._t);
    showAlert._t = setTimeout(()=> alertBox.classList.remove('show'), 3500);
}

function showSection(sectionId, evt){
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('active'));
    const section = document.getElementById(sectionId);
    if (section) section.classList.add('active');
    if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
    else if (evt && evt.target) evt.target.classList.add('active');

    if (sectionId === 'dashboard') updateDashboard();
    if (sectionId === 'viewBooks') displayAllBooks();
    if (sectionId === 'viewMembers') displayAllMembers();
}

function updateDashboard(){
    const stats = database.getStatistics();
    document.getElementById('totalBooks').textContent = stats.total;
    document.getElementById('availableBooks').textContent = stats.available;
    document.getElementById('borrowedBooks').textContent = stats.borrowed;
    document.getElementById('totalMembers').textContent = stats.totalMembers;
}

// Book UI
function handleAddBook(e){
    if (e) e.preventDefault();
    const bookId = document.getElementById('bookId').value.trim();
    const title = document.getElementById('bookTitle').value.trim();
    const author = document.getElementById('bookAuthor').value.trim();
    const res = database.addBook(bookId, title, author);
    if (res.success){
        showAlert(res.message, 'success');
        document.getElementById('addBookForm').reset();
        updateDashboard();
        displayAllBooks();
    } else showAlert(res.message, 'danger');
}

function handleSearchBook(){
    const bookId = document.getElementById('searchBookId').value.trim();
    const resultDiv = document.getElementById('bookSearchResult');
    if (!bookId){ showAlert('Please enter a Book ID!', 'danger'); return; }
    const book = database.getBook(bookId);
    if (!book){ resultDiv.innerHTML = '<div class="empty-state"><p>No book found with this ID.</p></div>'; showAlert('Book not found!', 'danger'); return; }
    resultDiv.innerHTML = bookTableHtml([book]);
    showAlert('Book found!', 'success');
}

function handleDeleteBook(bookId){
    if (!confirm('Delete this book?')) return;
    const res = database.deleteBook(bookId);
    if (res.success){ showAlert(res.message,'success'); displayAllBooks(); updateDashboard(); document.getElementById('bookSearchResult').innerHTML=''; }
    else showAlert(res.message,'danger');
}

function handleSortBooks(){
    const criteria = document.getElementById('sortBooks').value;
    if (!criteria) return displayAllBooks();
    const sorted = database.sortBooks(criteria);
    displayAllBooks(sorted);
    showAlert(`Books sorted by ${criteria}!`,'success');
}

function displayAllBooks(bookList=null){
    const container = document.getElementById('booksTable');
    const books = bookList || database.getAllBooks();
    if (!books || books.length===0){ container.innerHTML = '<div class="empty-state"><p>📚 No books available in the library.</p><p style="font-size:0.9em;color:#999;">Add some books to get started!</p></div>'; return; }
    container.innerHTML = bookTableHtml(books);
}

function bookTableHtml(books){
    let html = `
        <table>
          <thead>
            <tr>
              <th>Book ID</th><th>Title</th><th>Author</th><th>Status</th><th>Date Added</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>`;
    books.forEach(b => {
        html += `<tr>
            <td><strong>${b.bookId}</strong></td>
            <td>${escapeHtml(b.title)}</td>
            <td>${escapeHtml(b.author)}</td>
            <td><span class="status-badge status-${b.status.toLowerCase()}">${b.status}</span></td>
            <td>${b.dateAdded || ''}</td>
            <td>
              <button class="action-btn btn-danger" onclick="handleDeleteBook('${b.bookId}')">Delete</button>
            </td>
          </tr>`;
    });
    html += '</tbody></table>';
    return html;
}

// Member UI
function handleAddMember(e){
    if (e) e.preventDefault();
    const memberId = document.getElementById('memberId').value.trim();
    const name = document.getElementById('memberName').value.trim();
    const contact = document.getElementById('memberContact').value.trim();
    const res = database.addMember(memberId, name, contact);
    if (res.success){ showAlert(res.message,'success'); document.getElementById('addMemberForm').reset(); updateDashboard(); displayAllMembers(); }
    else showAlert(res.message,'danger');
}

function handleSearchMember(){
    const memberId = document.getElementById('searchMemberId').value.trim();
    const resultDiv = document.getElementById('memberSearchResult');
    if (!memberId){ showAlert('Please enter a Member ID!','danger'); return; }
    const member = database.getMember(memberId);
    if (!member){ resultDiv.innerHTML = '<div class="empty-state"><p>No member found with this ID.</p></div>'; showAlert('Member not found!','danger'); return; }
    resultDiv.innerHTML = memberTableHtml([member]); showAlert('Member found!','success');
}

function handleDeleteMember(memberId){
    if (!confirm('Delete this member?')) return;
    const res = database.deleteMember(memberId);
    if (res.success){ showAlert(res.message,'success'); displayAllMembers(); updateDashboard(); document.getElementById('memberSearchResult').innerHTML=''; }
    else showAlert(res.message,'danger');
}

function displayAllMembers(){
    const container = document.getElementById('membersTable');
    const members = database.getAllMembers();
    if (!members || members.length===0){ container.innerHTML = '<div class="empty-state"><p>👥 No members registered.</p><p style="font-size:0.9em;color:#999;">Add members to get started!</p></div>'; return; }
    container.innerHTML = memberTableHtml(members);
}

function memberTableHtml(members){
    let html = `
      <table>
        <thead>
          <tr><th>Member ID</th><th>Name</th><th>Contact</th><th>Date Registered</th><th>Actions</th></tr>
        </thead>
        <tbody>`;
    members.forEach(m => html += `<tr>
        <td><strong>${m.memberId}</strong></td>
        <td>${escapeHtml(m.name)}</td>
        <td>${escapeHtml(m.contact)}</td>
        <td>${m.dateRegistered || ''}</td>
        <td><button class="action-btn btn-danger" onclick="handleDeleteMember('${m.memberId}')">Delete</button></td>
      </tr>`);
    html += '</tbody></table>';
    return html;
}

// Borrow / Return
function handleBorrowBook(){
    const bookId = document.getElementById('borrowBookId').value.trim();
    if (!bookId){ showAlert('Please enter a Book ID!','danger'); return; }
    const book = database.getBook(bookId);
    if (!book){ showAlert('Book not found!','danger'); return; }
    if (book.status === 'Borrowed'){ showAlert('Book already borrowed!','danger'); return; }
    const res = database.updateBookStatus(bookId,'Borrowed');
    if (res.success){ showAlert('Book borrowed successfully!','success'); document.getElementById('borrowBookId').value=''; updateDashboard(); displayAllBooks(); }
}

function handleReturnBook(){
    const bookId = document.getElementById('borrowBookId').value.trim();
    if (!bookId){ showAlert('Please enter a Book ID!','danger'); return; }
    const book = database.getBook(bookId);
    if (!book){ showAlert('Book not found!','danger'); return; }
    if (book.status === 'Available'){ showAlert('Book is already available!','danger'); return; }
    const res = database.updateBookStatus(bookId,'Available');
    if (res.success){ showAlert('Book returned successfully!','success'); document.getElementById('borrowBookId').value=''; updateDashboard(); displayAllBooks(); }
}

// small util to avoid HTML injection
function escapeHtml(str){ return (str+'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

// Initialize UI on DOM ready
document.addEventListener('DOMContentLoaded', ()=>{
    // Attach global handlers if desired (forms already use onsubmit in markup)
    updateDashboard();
    displayAllBooks();
    displayAllMembers();
    // Ensure main dashboard button marked active
    const activeBtn = document.querySelector('.menu-btn.active');
    if (activeBtn) activeBtn.classList.add('active');
});