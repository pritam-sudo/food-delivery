<?php
session_start();
require_once 'admin/config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Fetch all dishes with restaurant information
try {
    $stmt = $pdo->prepare("
        SELECT d.*, r.name as restaurant_name 
        FROM dishes d 
        LEFT JOIN restaurants r ON d.restaurant_id = r.id 
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching dishes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!--=============== Favicon ===============-->
    <link
      rel="shortcut icon"
      href="./images/favicon-32x32.png"
      type="image/png"
    />
    <!--=============== Boxicons ===============-->
    <link
      href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css"
      rel="stylesheet"
    />
    <!--=============== Custom StyleSheet ===============-->
    <link rel="stylesheet" href="./css/styles.css" />
    <title>FDelivery - All Dishes</title>
  </head>
  <body>
    <!--=============== Header ===============-->
    <header class="header">
      <nav class="navbar">
        <div class="row d-flex container">
          <a href="" class="logo d-flex">
            <img src="./images/logo.png" alt="" />
          </a>

          <ul class="nav-list d-flex">
            <a href="index.html">Home</a>
            <a href="">About</a>
            <a href="dishes.html">Shop</a>
            <a href="">Recipes</a>
            <a href="">Contact</a>
            <span class="close d-flex"><i class="bx bx-x"></i></span>
          </ul>

          <div class="auth-links d-flex">
            <!--  -->
          </div>

          <div class="col d-flex">
            
            <div class="cart-icon d-flex">
              <i class="bx bx-shopping-bag"></i>
              <span>0</span>
            </div>
            <a href="profile.php" class="btn">Profile</a>
            <a href="dishes.php" class="btn">Order Food</a>
          </div>

          <!-- Hamburger -->
          <div class="hamburger d-flex">
            <i class="bx bx-menu"></i>
          </div>
        </div>
      </nav>
    </header>

    <!--=============== Dishes Section ===============-->
    <section class="dishes section">
      <div class="container">
        <h2 class="section-title">All Dishes</h2>
        
        <!-- Error message if any -->
        <?php if (isset($error)): ?>
          <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- Loading state -->
        <div class="loading-container" style="display: none;">
          <div class="loading-spinner"></div>
        </div>

        <!-- Filters -->
        <div class="filters d-flex">
          <span class="active" data-filter="all">All</span>
          <?php 
          // Get unique categories from dishes
          $categories = array_unique(array_column($dishes, 'category'));
          foreach ($categories as $category): 
            if (!empty($category)): 
          ?>
            <span data-filter="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></span>
          <?php endif; endforeach; ?>
        </div>

        <!-- Products Grid -->
        <div class="products grid">
          <?php if (empty($dishes)): ?>
            <div class="no-dishes-message">
              <p>No dishes available at the moment.</p>
            </div>
          <?php else: ?>
            <?php foreach ($dishes as $dish): ?>
              <div class="card" data-category="<?php echo htmlspecialchars($dish['category']); ?>">
                <div class="image">
                  <?php if (!empty($dish['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($dish['image_url']); ?>" alt="<?php echo htmlspecialchars($dish['title']); ?>" />
                  <?php else: ?>
                    <div class="placeholder-image">
                      <i class="bx bx-food-menu"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="card-content">
                  <h4><?php echo htmlspecialchars($dish['title']); ?></h4>
                  <div class="restaurant">
                    <span><i class="bx bx-building-house"></i> <?php echo htmlspecialchars($dish['restaurant_name']); ?></span>
                  </div>
                  <div class="price">
                    <span>$<?php echo number_format($dish['price'], 2); ?></span>
                  </div>
                  <div class="description">
                    <p><?php echo htmlspecialchars(substr($dish['description'], 0, 100)) . '...'; ?></p>
                  </div>
                  <div class="card-actions d-flex justify-content-center">
                    <?php if ($is_logged_in): ?>
                        <a href="order.php?dish_id=<?php echo $dish['id']; ?>" class="button" >Buy Now</a>
                    <?php else: ?>
                        <a href="login.php" class="button">Login to Buy</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!--=============== Footer ===============-->
    <footer class="footer">
      <div class="row container">
        <div class="col">
          <div class="logo d-flex">
            <img src="./images/logo.png" alt="logo" />
          </div>
          <p>
            Food is any substance consumed to provide nutritional support for an
            organism.
          </p>
          <div class="icons d-flex">
            <div class="icon d-flex">
              <i class="bx bxl-facebook"></i>
            </div>
            <div class="icon d-flex">
              <i class="bx bxl-twitter"></i>
            </div>
            <div class="icon d-flex">
              <i class="bx bxl-youtube"></i>
            </div>
          </div>
        </div>
        <div class="col">
          <div>
            <h4>Company</h4>
            <a href="">About Us</a>
            <a href="">Contact Us</a>
            <a href="">Careers</a>
            <a href="">Press</a>
          </div>
          <div>
            <h4>Services</h4>
            <a href="">Fast Delivery</a>
            <a href="">Food Order</a>
            <a href="">Catering</a>
            <a href="">Easy Payment</a>
          </div>
          <div>
            <h4>Support</h4>
            <a href="">Help Center</a>
            <a href="">Safety Center</a>
            <a href="">Community</a>
          </div>
        </div>
      </div>
    </footer>

    <!--=============== Custom JavaScript ===============-->
    <script>
      // Initialize mobile menu
      const hamburger = document.querySelector('.hamburger');
      const navList = document.querySelector('.nav-list');
      const close = document.querySelector('.close');

      hamburger.addEventListener('click', () => {
        navList.classList.add('show');
      });

      close.addEventListener('click', () => {
        navList.classList.remove('show');
      });

      // Initialize filter functionality
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

      // Initialize search functionality
      const searchInput = document.querySelector('input[type="search"]');

      searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();

        productCards.forEach(card => {
          const title = card.querySelector('h4').textContent.toLowerCase();
          const category = card.getAttribute('data-category').toLowerCase();
          
          if (title.includes(searchTerm) || category.includes(searchTerm)) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });

      // Add to cart functionality
      document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
          const card = this.closest('.card');
          const title = card.querySelector('h4').textContent;
          const price = card.querySelector('.price span').textContent;
          
          // Here you would typically send this data to your cart system
          alert(`Added ${title} to cart!`);
        });
      });

      // Show loading state on page load
      window.addEventListener('load', () => {
        const loadingContainer = document.querySelector('.loading-container');
        loadingContainer.style.display = 'none';
      });
   
  </body>
</html> 