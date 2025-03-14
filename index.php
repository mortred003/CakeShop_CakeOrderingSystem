<?php

include 'config.php'; /* fajl sadrzi konekcija za databaaza username pasvord host i nin path */

session_start();

if(isset($_SESSION['user_id'])){ /* otvarame sessija provervame id od user ako je nov user
                                                       dodavame id od sessija */
   $user_id = $_SESSION['user_id'];//user id zimame od session variabla
}else{
   $user_id = '';
};

if(isset($_POST['register'])){   /* php registracija */

   $name = $_POST['name']; /* so post zimame input od box html */
   $name = filter_var($name, FILTER_SANITIZE_STRING); /* filter za skidanje encoduane karakteri */
   $email = $_POST['email'];
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   $cpass = sha1($_POST['cpass'] );
   $cpass = filter_var($cpass, FILTER_SANITIZE_STRING);

   $select_user = $conn->prepare("SELECT * FROM `user` WHERE name = ? AND email = ?");
   $select_user->execute([$name, $email]); /* trazime a ima user so isti email vo db */

   if($select_user->rowCount() > 0){
      $message[] = 'username or email already exists!';  /* ima isti emal vo db,prekidame proces */
   }else{
      if($pass != $cpass){ /* provera pass so confirm pass */
         $message[] = 'confirm password not matched!';
      }else{
         $insert_user = $conn->prepare("INSERT INTO `user`(name, email, password) VALUES(?,?,?)"); /* spreame informacije za db */
         $insert_user->execute([$name, $email, $cpass]); /* ubacujeme inf vo db */
         $message[] = 'registered successfully, login now please!'; /* registracija uspesna login dostupen */
      }
   }

}

if(isset($_POST['update_qty'])){ /*cquantyti products vo korpa/cart */
   $_id = $_POST['cart_id']; /* corpa ima svoj id  */
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'cart quantity updated!';
}

if(isset($_GET['delete_cart_item'])){
   $delete_cart_id = $_GET['delete_cart_item']; /* itemi imaje opcija za brisene i davaje svoj id */
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");/* trazime item so dat id */
   $delete_cart_item->execute([$delete_cart_id]);
   header('location:index.php');
}

if(isset($_GET['logout'])){
   session_unset();              /* u toku logout skidame i unistavame sesija */
   session_destroy();
   header('location:index.php');
}

if(isset($_POST['add_to_cart'])){ /* u toku stavane item vo korpa */ 

   if($user_id == ''){  /* ako user nema id trazime login prvo */
      $message[] = 'please login first!';
   }else{   /* ako je loguan zimame informacije od product id ime cena sljika i quantity */

      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];
      $qty = $_POST['qty'];
      $qty = filter_var($qty, FILTER_SANITIZE_STRING);

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND name = ?");
      $select_cart->execute([$user_id, $name]);

      if($select_cart->rowCount() > 0){ /* provera a ima vec vo korpa tija fljedajeci a row od odredjen product popunjen tjst neje 0*/
         $message[] = 'already added to cart';
      }else{
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'added to cart!';
      }

   }

}

if(isset($_POST['order'])){

   if($user_id == ''){
      $message[] = 'please login first!';
   }else{
      $name = $_POST['name']; /* zimame informacije od box html */
      $name = filter_var($name, FILTER_SANITIZE_STRING);
      $number = $_POST['number'];
      $number = filter_var($number, FILTER_SANITIZE_STRING);
      $address = 'flat no.'.$_POST['flat'].', '.$_POST['street'].' - '.$_POST['pin_code'];
      $address = filter_var($address, FILTER_SANITIZE_STRING);
      $method = $_POST['method'];
      $method = filter_var($method, FILTER_SANITIZE_STRING);
      $total_price = $_POST['total_price'];
      $total_products = $_POST['total_products'];

      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?"); /* spremame i zimame producti od korpa */
      $select_cart->execute([$user_id]);

      if($select_cart->rowCount() > 0){ /* ako korpa ima producti ge prebacuje vo order i rasporedjujeme */
         $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, method, address, total_products, total_price) VALUES(?,?,?,?,?,?,?)");
         $insert_order->execute([$user_id, $name, $number, $method, $address, $total_products, $total_price]);
         $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
         $delete_cart->execute([$user_id]);
         $message[] = 'order placed successfully!'; /* nakon prebacujene brisime producti od kart */
      }else{
         $message[] = 'your cart empty!';
      }
   }

}

?>
                           
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- responsive site so javascri[t] -->
   <title>cakeShop</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){   /* davame alerts koje ge zimame so php i prajme vo html*/
         echo ' 
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<!-- header starts  -->

<header class="header">


   <section class="flex">

   <span translate="no">  <a href="#home" class="logo"><span>Sweet</span>Cake!</a></span>

      <div id="google_translate_element"></div> 
      <style type="text/css">
 
 /*google translate Dropdown */
 
 #google_translate_element select{
 background:#f0f8ff;
 color:#00dfdf;
 border: none;
 border-radius:3px;
 padding:6px 8px
 }
 
 /*google translate link | logo */
   .goog-logo-link,.goog-te-gadget span,div#goog-gt-{
   display:none!important;
   }
 .goog-te-gadget{
   color:transparent!important;
   font-size:0;
 }
 
 /* google translate banner-frame */ /* ne radi */
 
 .goog-te-banner-frame{
 display:none !important;
 }
 
 #goog-gt-tt, .goog-te-balloon-frame{display: none !important;}
.goog-text-highlight { background: none !important; box-shadow: none !important;}
 
 body{top:0!important;}
   </style>
 


      <nav class="navbar">
         <a href="#home">home</a>
         <a href="#about">about us</a>
         <a href="menu.php">menu</a>
         <a href="menu.php">order</a>
         <a href="#faq">faq</a>
       <!--  <a href="simple_menu.html">simple menu</a>   pdf od sve so ima -->
      </nav>

      <div class="icons">

   
<!-- ikone -->
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="user-btn" class="fas fa-user"></div>
         <div id="order-btn" class="fas fa-box"></div>
         <?php /* na ikona od korpa obavestvame koljko itema imaje */
            $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");/* prekoje user id zimame negova korpa */
            $count_cart_items->execute([$user_id]);
            $total_cart_items = $count_cart_items->rowCount();
         ?>
         <div id="cart-btn" class="fas fa-shopping-cart"><span>(<?= $total_cart_items; ?>)</span></div>
      </div>

   </section>

</header>

<!-- header ends -->

<div class="user-account">

   <section>

      <div id="close-account"><span>close</span></div>

      <div class="user">
         <?php /* zimame usera i negove informacije od db */
            $select_user = $conn->prepare("SELECT * FROM `user` WHERE id = ?");
            $select_user->execute([$user_id]);
            if($select_user->rowCount() > 0){
               while($fetch_user = $select_user->fetch(PDO::FETCH_ASSOC)){
                  echo '<p>welcome ! <span>'.$fetch_user['name'].'</span></p>';
                  echo '<a href="index.php?logout" class="btn">logout</a>'; /* logout button */
               }  
            }else{
               echo '<p><span>you are not logged in now!</span></p>';
            }
         ?>
      </div>

      <div class="display-orders">
         <?php
            $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");/* order zimame cart */
            $select_cart->execute([$user_id]);
            if($select_cart->rowCount() > 0){
               while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){/* sve dok > 0 kazuj */
                  echo '<p>'.$fetch_cart['name'].' <span>('.$fetch_cart['price'].' x '.$fetch_cart['quantity'].')</span></p>';
               }
            }else{
               echo '<p><span>your cart is empty!</span></p>';
            }
         ?>
      </div>

      <div class="flex">

         <form action="user_login.php" method="post">
            <h3>login now</h3>
            <input type="email" name="email" required class="box" placeholder="enter your email" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="enter your password" maxlength="20">
            <input type="submit" value="login now" name="login" class="btn">
         </form>

         <form action="" method="post">
            <h3>register now</h3>
            <input type="text" name="name" oninput="this.value = this.value.replace(/\s/g, '')" required class="box" placeholder="enter your username" maxlength="20">
            <input type="email" name="email" required class="box" placeholder="enter your email" maxlength="50">
            <input type="password" name="pass" required class="box" placeholder="enter your password" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="password" name="cpass" required class="box" placeholder="confirm your password" maxlength="20" oninput="this.value = this.value.replace(/\s/g, '')">
            <input type="submit" value="register now" name="register" class="btn">
         </form>

      </div>

   </section>

</div>

<div class="my-orders">

   <section>

      <div id="close-orders"><span>close</span></div>

      <h3 class="title"> my orders </h3>

      <?php
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){   
      ?>
      <div class="box">
         <p> placed on : <span><?= $fetch_orders['placed_on']; ?></span> </p>
         <p> name : <span><?= $fetch_orders['name']; ?></span> </p>
         <p> number : <span><?= $fetch_orders['number']; ?></span> </p>
         <p> address : <span><?= $fetch_orders['address']; ?></span> </p>
         <p> payment method : <span><?= $fetch_orders['method']; ?></span> </p>
         <p> total_orders : <span><?= $fetch_orders['total_products']; ?></span> </p>
         <p> total price : <span>eur:<?= $fetch_orders['total_price']; ?>/-</span> </p>
         <p> payment status : <span style="color:<?php if($fetch_orders['payment_status'] == 'pending'){ echo 'red'; }else{ echo 'green'; }; ?>"><?= $fetch_orders['payment_status']; ?></span> </p>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">nothing ordered yet!</p>';
      }
      ?>

   </section>

</div>

<div class="shopping-cart">

   <section>

      <div id="close-cart"><span>close</span></div>

      <?php
         $grand_total = 0;
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
              $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']);
              $grand_total += $sub_total; 
      ?>
      <div class="box">
         <a href="index.php?delete_cart_item=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('delete this cart item?');"></a>
         <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
         <div class="content">
          <p> <?= $fetch_cart['name']; ?> <span>(<?= $fetch_cart['price']; ?> x <?= $fetch_cart['quantity']; ?>)</span></p>
          <form action="" method="post">
             <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
             <input type="number" name="qty" class="qty" min="1" max="99" value="<?= $fetch_cart['quantity']; ?>" onkeypress="if(this.value.length == 2) return false;">
               <button type="submit" class="fas fa-edit" name="update_qty"></button>
          </form>
         </div>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty"><span>your cart is empty!</span></p>';
      }
      ?>

      <div class="cart-total"> grand total : <span>eur:<?= $grand_total; ?>/-</span></div>

      <a href="#order" class="btn">order now</a>

   </section>

</div>

<div class="home-bg">

   <section class="home" id="home">

      <div class="slide-container">

         <div class="slide active">
            <div class="image">
               <img src="images/home-img-1.png" alt="">
            </div>
            <div class="content">
               <h3>Big Cakes</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/home-img-2.png" alt="">
            </div>
            <div class="content">
               <h3>Chocolate</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

         <div class="slide">
            <div class="image">
               <img src="images/home-img-3.png" alt="">
            </div>
            <div class="content">
               <h3>Sweet Desserts</h3>
               <div class="fas fa-angle-left" onclick="prev()"></div>
               <div class="fas fa-angle-right" onclick="next()"></div>
            </div>
         </div>

      </div>

   </section>

</div>

<!-- about  starts  -->

<section class="about" id="about">

   <h1 class="heading">about us</h1>

   <div class="box-container">

      <div class="box">
         <img src="images/cake1.jpeg" alt="">
         <h3>made with love</h3>
         <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Illum quae amet beatae magni numquam facere sit. Tempora vel laboriosam repudiandae!</p>
         <a href="menu.php" class="btn">our menu</a>
      </div>

      <div class="box">
         <img src="images/about-2.png" alt="">
         <h3>share with freinds</h3>
         <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Illum quae amet beatae magni numquam facere sit. Tempora vel laboriosam repudiandae!</p>
         <a href="menu.php" class="btn">our menu</a>
      </div>

   </div>

</section>

<!-- about  ends -->




<!-- faq  starts  -->

<section class="faq" id="faq">

   <h1 class="heading">FAQ</h1>

   <div class="accordion-container">

      <div class="accordion active">
         <div class="accordion-heading">
            <span>how does it work?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, quas. Quidem minima veniam accusantium maxime, doloremque iusto deleniti veritatis quos.
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>how long does it take for delivery?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, quas. Quidem minima veniam accusantium maxime, doloremque iusto deleniti veritatis quos.
         </p>
      </div>

      <div class="accordion">
         <div class="accordion-heading">
            <span>can I order for huge parties?</span>
            <i class="fas fa-angle-down"></i>
         </div>
         <p class="accrodion-content">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, quas. Quidem minima veniam accusantium maxime, doloremque iusto deleniti veritatis quos.
         </p>
      </div>

   </div>

</section>

<!-- faq  ends -->

<!-- footer  starts  -->

<section class="footer">

   <div class="box-container">

      <div class="box">
         <i class="fas fa-phone"></i>
         <h3>phone number</h3>
         <p></p>
         <p>+383-45-957541</p>
      </div>

      <div class="box">
         <i class="fas fa-map-marker-alt"></i>
         <h3>our address</h3>
         <p>Krushevo/Dragsh</p>
      </div>

      <div class="box">
         <i class="fas fa-clock"></i>
         <h3>opening hours</h3>
         <p>09:00</p>
      </div>

      <div class="box">
         <i class="fas fa-envelope"></i>
         <h3>email address</h3>
         <p>@gmail.com</p>
         <p>@gmail.com</p>
      </div>
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d163720.98491002512!2d8.471411958755551!3d50.121094886106846!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47bd096f477096c5%3A0x422435029b0c600!2sFrankfurt%2C%20Germany!5e0!3m2!1sen!2s!4v1741989244595!5m2!1sen!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>  
   </div>

   <div class="credit">
      &copy; copyright @ <?= date('Y'); ?> by <span>ox mortred</span> | all rights reserved!  <a href="portfolio.html" id="portfolio"> @mortredservice </a>
   </div>

</section>

<!-- footer  ends -->

<style>
a:portfolio {
  color: green;
  background-color: transparent;
  text-decoration: none;
}
</style>








<!-- custom js file link  -->
<script src="js/script.js"></script>


<!-- google translate script -->
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en' , includedLanguages: 'sq,sr,mk'}, 'google_translate_element');
}
</script>

<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>



</body>
</html>