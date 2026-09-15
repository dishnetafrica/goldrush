import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/add_money_controller/add_money_controller.dart';
import '../../routes/routes.dart';
import '../common/app_bar/primary_app_bar.dart';

class WebPaymentScreen extends StatelessWidget {
  WebPaymentScreen({super.key});
  final controller = Get.put(AddMoneyController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const PrimaryAppBar(""),
      body: Obx(
        () => controller.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  InAppWebView _bodyWidget(BuildContext context) {
    final paymentUrl = controller.addMoneyGatewayModel.data.redirectUrl;

    return InAppWebView(
      initialUrlRequest: URLRequest(url: WebUri(paymentUrl)),
      onWebViewCreated: (InAppWebViewController controller) {},
      onProgressChanged: (InAppWebViewController controller, int progress) {},
      onLoadStop: (InAppWebViewController controller, url) async {
        if (url.toString().contains('success/response') ||
            url.toString().contains('sslcommerz/success') ||
            url.toString().contains('flutterwave/callback') ||
            url.toString().contains('razor/callback') ||
            url.toString().contains('qrpay/callback') ||
            url.toString().contains('/payment/confirmed/') ||
            url.toString().contains('/payment/success/')) {
          Get.toNamed(Routes.addMoneyCongratulation);
        } else if (url.toString().contains('/cancel/response')) {
          Get.close(1);
        }
      },
    );
  }
}
