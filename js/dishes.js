// Fetch and display dishes
async function loadDishes() {
  try {
    const response = await fetch('../data/products.json');
    const data = await response.json();
    const products = data.products;
    const productsGrid = document.querySelector('.products.grid');

    // Clear existing content
    productsGrid.innerHTML = '';

    // Create product cards
    products.forEach(product => {
      const productCard = document.createElement('div');
      productCard.className = 'card';
      productCard.setAttribute('data-category', product.category);
      
      productCard.innerHTML = `
        <div class="image">
          <img src="${product.url}" alt="${product.title}" />
        </div>
        <div class="rating">
          <span><i class="bx bxs-star"></i> 5.0</span>
        </div>
        <h4>${product.title}</h4>
        <div class="price">
          <span>$${product.price}</span>
        </div>
        <button class="button">Add to Cart</button>
      `;

      productsGrid.appendChild(productCard);
    });

    // Initialize filter functionality
    initializeFilters();
  } catch (error) {
    console.error('Error loading dishes:', error);
  }
}

// Initialize filter functionality
function initializeFilters() {
  const filterSpans = document.querySelectorAll('.filters span');
  const productCards = document.querySelectorAll('.products .card');

  filterSpans.forEach(span => {
    span.addEventListener('click', () => {
      // Remove active class from all filters
      filterSpans.forEach(s => s.classList.remove('active'));
      // Add active class to clicked filter
      span.classList.add('active');

      const filter = span.getAttribute('data-filter');

      productCards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-category') === filter) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
}

// Initialize mobile menu
function initializeMobileMenu() {
  const hamburger = document.querySelector('.hamburger');
  const navList = document.querySelector('.nav-list');
  const close = document.querySelector('.close');

  hamburger.addEventListener('click', () => {
    navList.classList.add('show');
  });

  close.addEventListener('click', () => {
    navList.classList.remove('show');
  });
}

// Initialize search functionality
function initializeSearch() {
  const searchInput = document.querySelector('input[type="search"]');
  const productCards = document.querySelectorAll('.products .card');

  searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();

    productCards.forEach(card => {
      const title = card.querySelector('h4').textContent.toLowerCase();
      if (title.includes(searchTerm)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  });
}

// Initialize cart functionality
function initializeCart() {
  const addToCartButtons = document.querySelectorAll('.button');
  const cartCount = document.querySelector('.cart-icon span');
  let count = 0;

  addToCartButtons.forEach(button => {
    button.addEventListener('click', () => {
      count++;
      cartCount.textContent = count;
      button.textContent = 'Added to Cart';
      button.style.backgroundColor = '#4CAF50';
      
      setTimeout(() => {
        button.textContent = 'Add to Cart';
        button.style.backgroundColor = '';
      }, 2000);
    });
  });
}

// Initialize everything when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  loadDishes();
  initializeMobileMenu();
  initializeSearch();
  initializeCart();
}); 