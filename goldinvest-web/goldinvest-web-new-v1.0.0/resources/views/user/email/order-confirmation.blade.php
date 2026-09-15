<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>GoldInvest | Invest And Safe Your Money</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@200;300;400;500;600;700;800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <style>
        ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .booking-area .content .list {
        margin-top: 10px;
        }
        .booking-area .content .list li {
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        color: #363333;
        border-bottom: 1px solid #eee;
        }
        @media only screen and (max-width: 470px) {
        .booking-area .content .list li {
            font-size: 12px;
            font-weight: 500;
        }
        }
        @media only screen and (max-width: 350px) {
        .booking-area .content .list li {
            font-size: 11px;
            font-weight: 500;
        }
        }
        .booking-area .content .list li span {
        text-transform: capitalize;
        color: #6c7176;
        font-weight: 500;
        font-size: 13px;
        padding-left: 5px;
        }
        @media only screen and (max-width: 470px) {
        .booking-area .content .list li span {
            font-size: 10px;
            font-weight: 400;
        }
        }
        @media only screen and (max-width: 350px) {
        .booking-area .content .list li span {
            font-size: 9px;
            font-weight: 400;
        }
        }
        .booking-area .content .list li + li {
        margin-top: 15px;
        }
        .gs li {
            margin-left: 0 !important;
        }
    </style>
</head>
<body style="font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 500; line-height: 1.5em; color: #6c7176;">



<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Email Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout:fixed;" id="bodyTable">
	<tbody>
		<tr>
			<td align="center" valign="top" id="bodyCell">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" class="wrapperBody" style="max-width:600px">
					<tbody>
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableCard">
									<tbody>
                                        <tr>
                                            <td style="padding: 20px;
                                            border: 1px solid #eee;
                                            border-radius: 20px;
                                            -webkit-box-shadow: rgba(0, 0, 0, 0.15) 0px 3px 7px 0px;
                                            box-shadow: rgba(0, 0, 0, 0.15) 0px 3px 7px 0px; background: #fff;">
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <h3 style="font-size: 24px;
                                                            font-weight: 700; clear: both; line-height: 1.3em; color: #1A043F; margin-top: 0; margin-bottom: 0.5rem;">{{ __("Order Details") }}</h3>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <table class="list-wrapper" style="width: 100%">
                                                    <tbody class="list" style="margin-top: 10px;">
                                                        <tr>
                                                            <div class="booking-area">
                                                                <div class="content pt-0">
                                                                    <div class="list-wrapper">
                                                                        <ul class="list" style="width: 100%;">
                                                                            <li style="margin-left: 0 !important;">{{ __("Item") }}:<span style="float: right !important;">{{ $data['item']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Quantity") }}:<span style="float: right !important;">{{ $data['quantity']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Mobile") }}:<span style="float: right !important;">{{ $data['mobile']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Address") }}:<span style="float: right !important;">{{ $data['address']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("City") }}:<span style="float: right !important;">{{ $data['city']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Zip Code") }}:<span style="float: right !important;">{{ $data['zip_code']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("State") }}:<span style="float: right !important;text-transform: lowercase;">{{ $data['state']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Country") }}:<span style="float: right !important;">{{ $data['country']}}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Payment Type") }}:<span style="float: right !important;">@if($data['payment_type'] == 'user_wallet'){{ __("User Wallet") }}@else {{ __("Cash on Delivery") }} @endif</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Price") }}:<span style="float: right !important;">{{ get_amount($data['price'])}} {{ $default_currency->code }}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Order Status") }}:<span style="float: right !important;">@if($data['order_status'] == 1){{ __("Accepted") }} @elseif($data['order_status'] == 2){{ __("Ongoing") }} @elseif($data['order_status'] == 3){{ __("Delivered") }} @else {{ __("N/A") }} @endif</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Delivery Charge") }}:<span style="float: right !important;">{{ get_amount($data['charge'])}} {{ $default_currency->code }}</span></li>
                                                                            <li style="margin-left: 0 !important;">{{ __("Total Amount") }}:<span style="float: right !important;">{{ get_amount($data['total_amount'])}} {{ $default_currency->code }}</span></li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Email Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


</body>
</html>
