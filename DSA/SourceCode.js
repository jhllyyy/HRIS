/*
 * =============================================
 * Project: LibraLink - Library Management System
 * Authors: Mark Yien Molina, Justine Lee Arcilla, Renz Kylle Lara, 
 *          Anghel Lagrimas, Jhon Lloyd B. Barela [2411017, 2412587, 2410971, 2412851, 2412601]
 * Date: 12/16/2025
 * Description:A GUI based library management system using Map and Array data structures.
 * =============================================
 */

// ============================================
// AUTHENTICATION MODULE
// ============================================

/**
 * Authentication credentials for system access
 */
const AUTH_USER = {
    username: "admin",
    password: "admin123"
};

/**
 * Handles user login authentication
 * Validates credentials and grants system access
 */
function handleLogin() {
    const username = document.getElementById("loginUsername").value.trim();
    const password = document.getElementById("loginPassword").value.trim();
    const error = document.getElementById("loginError");

    // Validate credentials against stored user
    if (username === AUTH_USER.username && password === AUTH_USER.password) {
        // Store session state in memory (not using localStorage per requirements)
        sessionStorage.setItem("isLoggedIn", "true");
        showApp();
    } else {
        error.textContent = "Invalid username or password!";
    }
}

/**
 * Handles user logout
 * Clears session and reloads to login page
 */
function handleLogout() {
    sessionStorage.clear();
    location.reload();
}

/**
 * Displays main application interface
 * Hides login page and shows dashboard
 */
function showApp() {
    document.getElementById("loginPage").style.display = "none";
    document.getElementById("appContainer").style.display = "block";
}

// ============================================
// LIBRARY DATABASE CLASS
// ============================================

/**
 * Main database class for library management
 * Uses Map for O(1) lookups and Arrays for ordered storage
 */
class LibraryDatabase {
    constructor() {
        // Map data structures for fast book/member lookups by ID
        this.books = new Map();
        this.members = new Map();
        
        // Array structures for ordered iteration and display
        this.bookList = [];
        this.memberList = [];
        
        // Load any existing data from session storage
        this.loadFromStorage();
    }

    /**
     * Persists current state to session storage
     * Converts Maps to Arrays for JSON serialization
     */
    saveToStorage() {
        sessionStorage.setItem('library_books', JSON.stringify(this.bookList));
        sessionStorage.setItem('library_members', JSON.stringify(this.memberList));
    }

    /**
     * Loads saved data from session storage
     * Reconstructs Map structures from stored arrays
     */
    loadFromStorage() {
        try {
            const books = JSON.parse(sessionStorage.getItem('library_books') || '[]');
            const members = JSON.parse(sessionStorage.getItem('library_members') || '[]');
            
            this.bookList = books || [];
            this.memberList = members || [];
            
            // Rebuild Maps from arrays for O(1) access
            this.books = new Map(this.bookList.map(b => [b.bookId, b]));
            this.members = new Map(this.memberList.map(m => [m.memberId, m]));
        } catch (e) {
            // Initialize empty structures on error
            this.bookList = [];
            this.memberList = [];
            this.books = new Map();
            this.members = new Map();
        }
    }

    // ============================================
    // BOOK OPERATIONS
    // ============================================

    /**
     * Adds a new book to the library catalog
     * @param {string} bookId - Unique book identifier
     * @param {string} title - Book title
     * @param {string} author - Book author
     * @returns {Object} Result object with success status and message
     */
    addBook(bookId, title, author) {
        // Input validation
        if (!bookId || !title || !author) {
            return { success: false, message: 'All fields are required.' };
        }
        
        // Check for duplicate book ID
        if (this.books.has(bookId)) {
            return { success: false, message: 'Book ID already exists!' };
        }

        // Create book object with metadata
        const book = { 
            bookId, 
            title, 
            author, 
            status: 'Available', 
            dateAdded: new Date().toLocaleString() 
        };
        
        // Add to both Map (for fast lookup) and Array (for ordered display)
        this.books.set(bookId, book);
        this.bookList.push(book);
        this.saveToStorage();
        
        return { success: true, message: 'Book added successfully!', data: book };
    }

    /**
     * Retrieves a book by ID
     * @param {string} bookId - Book identifier
     * @returns {Object|undefined} Book object or undefined if not found
     */
    getBook(bookId) { 
        return this.books.get(bookId); 
    }

    /**
     * Returns all books in the catalog
     * @returns {Array} Array of all book objects
     */
    getAllBooks() { 
        return [...this.bookList]; 
    }

    /**
     * Removes a book from the catalog
     * @param {string} bookId - Book identifier to delete
     * @returns {Object} Result object with success status
     */
    deleteBook(bookId) {
        if (!this.books.has(bookId)) {
            return { success: false, message: 'Book not found!' };
        }
        
        // Remove from both Map and Array structures
        this.books.delete(bookId);
        this.bookList = this.bookList.filter(b => b.bookId !== bookId);
        this.saveToStorage();
        
        return { success: true, message: 'Book deleted successfully!' };
    }

    /**
     * Updates the availability status of a book
     * @param {string} bookId - Book identifier
     * @param {string} status - New status ('Available' or 'Borrowed')
     * @returns {Object} Result object with updated book data
     */
    updateBookStatus(bookId, status) {
        const book = this.books.get(bookId);
        if (!book) {
            return { success: false, message: 'Book not found!' };
        }
        
        // Update status in both Map and Array references
        book.status = status;
        this.bookList = this.bookList.map(b => (b.bookId === bookId ? book : b));
        this.saveToStorage();
        
        return { success: true, message: 'Book status updated!', data: book };
    }

    // ============================================
    // MEMBER OPERATIONS
    // ============================================

    /**
     * Registers a new library member
     * @param {string} memberId - Unique member identifier
     * @param {string} name - Member name
     * @param {string} contact - Contact information
     * @returns {Object} Result object with success status
     */
    addMember(memberId, name, contact) {
        if (!memberId || !name || !contact) {
            return { success: false, message: 'All fields are required.' };
        }
        
        if (this.members.has(memberId)) {
            return { success: false, message: 'Member ID already exists!' };
        }
        
        const member = { 
            memberId, 
            name, 
            contact, 
            dateRegistered: new Date().toLocaleString() 
        };
        
        this.members.set(memberId, member);
        this.memberList.push(member);
        this.saveToStorage();
        
        return { success: true, message: 'Member added successfully!', data: member };
    }

    /**
     * Retrieves a member by ID
     * @param {string} memberId - Member identifier
     * @returns {Object|undefined} Member object or undefined
     */
    getMember(memberId) { 
        return this.members.get(memberId); 
    }

    /**
     * Returns all registered members
     * @returns {Array} Array of all member objects
     */
    getAllMembers() { 
        return [...this.memberList]; 
    }

    /**
     * Removes a member from the system
     * @param {string} memberId - Member identifier to delete
     * @returns {Object} Result object with success status
     */
    deleteMember(memberId) {
        if (!this.members.has(memberId)) {
            return { success: false, message: 'Member not found!' };
        }
        
        this.members.delete(memberId);
        this.memberList = this.memberList.filter(m => m.memberId !== memberId);
        this.saveToStorage();
        
        return { success: true, message: 'Member deleted successfully!' };
    }

    // ============================================
    // STATISTICS & UTILITIES
    // ============================================

    /**
     * Calculates library statistics for dashboard
     * @returns {Object} Statistics object with counts
     */
    getStatistics() {
        const total = this.bookList.length;
        const available = this.bookList.filter(b => b.status === 'Available').length;
        const borrowed = this.bookList.filter(b => b.status === 'Borrowed').length;
        const totalMembers = this.memberList.length;
        
        return { total, available, borrowed, totalMembers };
    }

    /**
     * Sorts books by specified criteria
     * @param {string} criteria - Sort field ('id', 'title', 'author', 'status')
     * @returns {Array} Sorted array of books
     */
    sortBooks(criteria) {
        const sorted = [...this.bookList];
        
        switch (criteria) {
            case 'id':
                return sorted.sort((a, b) => a.bookId.localeCompare(b.bookId));
            case 'title':
                return sorted.sort((a, b) => a.title.localeCompare(b.title));
            case 'author':
                return sorted.sort((a, b) => a.author.localeCompare(b.author));
            case 'status':
                return sorted.sort((a, b) => a.status.localeCompare(b.status));
            default:
                return sorted;
        }
    }
}

// ============================================
// GLOBAL DATABASE INSTANCE
// ============================================

/** Global instance of the library database */
const database = new LibraryDatabase();

// ============================================
// UI HELPER FUNCTIONS
// ============================================

/**
 * Displays alert messages to user
 * @param {string} message - Message to display
 * @param {string} type - Alert type ('success' or 'danger')
 */
function showAlert(message, type = 'success') {
    const alertBox = document.getElementById('alertBox');
    if (!alertBox) return;
    
    alertBox.className = `alert alert-${type} show`;
    alertBox.textContent = message;
    
    // Auto-hide alert after 3.5 seconds
    clearTimeout(showAlert._t);
    showAlert._t = setTimeout(() => alertBox.classList.remove('show'), 3500);
}

/**
 * Switches between different sections of the application
 * @param {string} sectionId - ID of section to display
 * @param {Event} evt - Click event object
 */
function showSection(sectionId, evt) {
    // Hide all sections and remove active state from buttons
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-btn').forEach(b => b.classList.remove('active'));
    
    // Show selected section
    const section = document.getElementById(sectionId);
    if (section) section.classList.add('active');
    
    // Mark clicked button as active
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    } else if (evt && evt.target) {
        evt.target.classList.add('active');
    }

    // Refresh data displays when navigating to specific sections
    if (sectionId === 'dashboard') updateDashboard();
    if (sectionId === 'viewBooks') displayAllBooks();
    if (sectionId === 'viewMembers') displayAllMembers();
}

/**
 * Updates dashboard statistics
 */
function updateDashboard() {
    const stats = database.getStatistics();
    document.getElementById('totalBooks').textContent = stats.total;
    document.getElementById('availableBooks').textContent = stats.available;
    document.getElementById('borrowedBooks').textContent = stats.borrowed;
    document.getElementById('totalMembers').textContent = stats.totalMembers;
}

// ============================================
// BOOK UI HANDLERS
// ============================================

/**
 * Handles book addition form submission
 * @param {Event} e - Form submit event
 */
function handleAddBook(e) {
    if (e) e.preventDefault();
    
    const bookId = document.getElementById('bookId').value.trim();
    const title = document.getElementById('bookTitle').value.trim();
    const author = document.getElementById('bookAuthor').value.trim();
    
    const res = database.addBook(bookId, title, author);
    
    if (res.success) {
        showAlert(res.message, 'success');
        document.getElementById('addBookForm').reset();
        updateDashboard();
        displayAllBooks();
    } else {
        showAlert(res.message, 'danger');
    }
}

/**
 * Searches for a book by ID
 */
function handleSearchBook() {
    const bookId = document.getElementById('searchBookId').value.trim();
    const resultDiv = document.getElementById('bookSearchResult');
    
    if (!bookId) {
        showAlert('Please enter a Book ID!', 'danger');
        return;
    }
    
    const book = database.getBook(bookId);
    
    if (!book) {
        resultDiv.innerHTML = '<div class="empty-state"><p>No book found with this ID.</p></div>';
        showAlert('Book not found!', 'danger');
        return;
    }
    
    resultDiv.innerHTML = bookTableHtml([book]);
    showAlert('Book found!', 'success');
}

/**
 * Deletes a book from the catalog
 * @param {string} bookId - ID of book to delete
 */
function handleDeleteBook(bookId) {
    if (!confirm('Delete this book?')) return;
    
    const res = database.deleteBook(bookId);
    
    if (res.success) {
        showAlert(res.message, 'success');
        displayAllBooks();
        updateDashboard();
        document.getElementById('bookSearchResult').innerHTML = '';
    } else {
        showAlert(res.message, 'danger');
    }
}

/**
 * Handles book sorting by selected criteria
 */
function handleSortBooks() {
    const criteria = document.getElementById('sortBooks').value;
    
    if (!criteria) return displayAllBooks();
    
    const sorted = database.sortBooks(criteria);
    displayAllBooks(sorted);
    showAlert(`Books sorted by ${criteria}!`, 'success');
}

/**
 * Displays all books in a table
 * @param {Array} bookList - Optional pre-sorted book array
 */
function displayAllBooks(bookList = null) {
    const container = document.getElementById('booksTable');
    const books = bookList || database.getAllBooks();
    
    if (!books || books.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>📚 No books available in the library.</p><p style="font-size:0.9em;color:#999;">Add some books to get started!</p></div>';
        return;
    }
    
    container.innerHTML = bookTableHtml(books);
}

/**
 * Generates HTML table for book display
 * @param {Array} books - Array of book objects
 * @returns {string} HTML table string
 */
function bookTableHtml(books) {
    let html = `
        <table>
          <thead>
            <tr>
              <th>Book ID</th>
              <th>Title</th>
              <th>Author</th>
              <th>Status</th>
              <th>Date Added</th>
              <th>Actions</th>
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

// ============================================
// MEMBER UI HANDLERS
// ============================================

/**
 * Handles member registration form submission
 * @param {Event} e - Form submit event
 */
function handleAddMember(e) {
    if (e) e.preventDefault();
    
    const memberId = document.getElementById('memberId').value.trim();
    const name = document.getElementById('memberName').value.trim();
    const contact = document.getElementById('memberContact').value.trim();
    
    const res = database.addMember(memberId, name, contact);
    
    if (res.success) {
        showAlert(res.message, 'success');
        document.getElementById('addMemberForm').reset();
        updateDashboard();
        displayAllMembers();
    } else {
        showAlert(res.message, 'danger');
    }
}

/**
 * Searches for a member by ID
 */
function handleSearchMember() {
    const memberId = document.getElementById('searchMemberId').value.trim();
    const resultDiv = document.getElementById('memberSearchResult');
    
    if (!memberId) {
        showAlert('Please enter a Member ID!', 'danger');
        return;
    }
    
    const member = database.getMember(memberId);
    
    if (!member) {
        resultDiv.innerHTML = '<div class="empty-state"><p>No member found with this ID.</p></div>';
        showAlert('Member not found!', 'danger');
        return;
    }
    
    resultDiv.innerHTML = memberTableHtml([member]);
    showAlert('Member found!', 'success');
}

/**
 * Deletes a member from the system
 * @param {string} memberId - ID of member to delete
 */
function handleDeleteMember(memberId) {
    if (!confirm('Delete this member?')) return;
    
    const res = database.deleteMember(memberId);
    
    if (res.success) {
        showAlert(res.message, 'success');
        displayAllMembers();
        updateDashboard();
        document.getElementById('memberSearchResult').innerHTML = '';
    } else {
        showAlert(res.message, 'danger');
    }
}

/**
 * Displays all members in a table
 */
function displayAllMembers() {
    const container = document.getElementById('membersTable');
    const members = database.getAllMembers();
    
    if (!members || members.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>👥 No members registered.</p><p style="font-size:0.9em;color:#999;">Add members to get started!</p></div>';
        return;
    }
    
    container.innerHTML = memberTableHtml(members);
}

/**
 * Generates HTML table for member display
 * @param {Array} members - Array of member objects
 * @returns {string} HTML table string
 */
function memberTableHtml(members) {
    let html = `
      <table>
        <thead>
          <tr>
            <th>Member ID</th>
            <th>Name</th>
            <th>Contact</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>`;
    
    members.forEach(m => {
        html += `<tr>
            <td><strong>${m.memberId}</strong></td>
            <td>${escapeHtml(m.name)}</td>
            <td>${escapeHtml(m.contact)}</td>
            <td>${m.dateRegistered || ''}</td>
            <td>
              <button class="action-btn btn-danger" onclick="handleDeleteMember('${m.memberId}')">Delete</button>
            </td>
          </tr>`;
    });
    
    html += '</tbody></table>';
    return html;
}

// ============================================
// BORROW/RETURN HANDLERS
// ============================================

/**
 * Processes a book borrowing transaction
 */
function handleBorrowBook() {
    const bookId = document.getElementById('borrowBookId').value.trim();
    
    if (!bookId) {
        showAlert('Please enter a Book ID!', 'danger');
        return;
    }
    
    const book = database.getBook(bookId);
    
    if (!book) {
        showAlert('Book not found!', 'danger');
        return;
    }
    
    if (book.status === 'Borrowed') {
        showAlert('Book already borrowed!', 'danger');
        return;
    }
    
    const res = database.updateBookStatus(bookId, 'Borrowed');
    
    if (res.success) {
        showAlert('Book borrowed successfully!', 'success');
        document.getElementById('borrowBookId').value = '';
        updateDashboard();
        displayAllBooks();
    }
}

/**
 * Processes a book return transaction
 */
function handleReturnBook() {
    const bookId = document.getElementById('borrowBookId').value.trim();
    
    if (!bookId) {
        showAlert('Please enter a Book ID!', 'danger');
        return;
    }
    
    const book = database.getBook(bookId);
    
    if (!book) {
        showAlert('Book not found!', 'danger');
        return;
    }
    
    if (book.status === 'Available') {
        showAlert('Book is already available!', 'danger');
        return;
    }
    
    const res = database.updateBookStatus(bookId, 'Available');
    
    if (res.success) {
        showAlert('Book returned successfully!', 'success');
        document.getElementById('borrowBookId').value = '';
        updateDashboard();
        displayAllBooks();
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Escapes HTML special characters to prevent XSS attacks
 * @param {string} str - String to escape
 * @returns {string} Escaped string
 */
function escapeHtml(str) {
    return (str + '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ============================================
// INITIALIZATION
// ============================================

/**
 * Initializes the application on page load
 * Checks authentication and sets up initial displays
 */
document.addEventListener('DOMContentLoaded', () => {
    const loggedIn = sessionStorage.getItem("isLoggedIn");

    if (loggedIn === "true") {
        showApp();
        updateDashboard();
        displayAllBooks();
        displayAllMembers();
    } else {
        document.getElementById("loginPage").style.display = "flex";
        document.getElementById("appContainer").style.display = "none";
    }

});
