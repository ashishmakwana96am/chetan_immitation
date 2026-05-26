<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Admin Modules:
// 	Location
// 		Shop 1
// 		Shop 2
// 	Category
// 		Bracelates
// 		Neckles
	
// 	Admin Users
// 		Location Selection while adding Users
		
// 	Roles & Permission
// 	Product
// 		Product Name
// 		category
		
// 	Purchase Products
// 		purvchase invoice
// 		supplier
// 		purchase price
// 		purchase qty
		
// 		Name		Price	 QTY
// 		Braclets	 150	 100
// 		Neckless	 200	 50
		
// 		Location Allocate:
// 			Bracelet 150 Overall QTY:100
// 				Shop1 - 50 QTY
// 				Shop2 - 50 QTY
				
// 			Neckles 200 Overall QTY:50
// 				Shop1 - 25 QTY
// 				Shop2 - 25 QTY
			
			
// 		Confirm Purchase
		
		
// 	Sale:
// 		Walkin Customer  | ADD Customer
// 		Product QTY Discount price total
		
		
// 	Orders
	
// 	Customer
		
// 	Reports:
// 		Products Reports
// 		Purchase Reports
// 		Sale Report
// 		Profit & Loss Report
// 		Stock Inventory
	
	
// Website:
// 	Login | Registration
// 	Product Listing
// 	Add To Cart
// 	Checkout
// 	Wish List
// 	My Orders
// 	My Profile








// // improving below payment status and order status flow after that we will impliment it 
// let's we mange properly manage status and payment status functionality

// - if mark as paid then we will create entry on one table for record we will change payment status to paid
// - if order status try to complete then we first check is order paid then we will change order status to complete
// - if try to change order status to Cancel then first we check is that payment paid then we will change payment status to refunded and order status to cancelled. and if payment status is unpaid or pending then we change payment status to unpaid and order status to cancelled.

// Note: on table we store payment record also manage.