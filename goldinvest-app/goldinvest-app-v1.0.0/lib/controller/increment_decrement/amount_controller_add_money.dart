import 'package:flutter/material.dart';
import 'package:get/get.dart';

class AmountControllerAddMoney extends GetxController {
  RxDouble baseCurrency = 0.0.obs;

  final amountController = TextEditingController();

  void increaseAmount() {
    double amount = amountController.text.isNotEmpty
        ? double.parse(amountController.text)
        : 0.0;
    amountController.text = (amount + 10).toStringAsFixed(0);
  }

  void decreaseAmount() {
    double amount = amountController.text.isNotEmpty
        ? double.parse(amountController.text)
        : 0.0;
    if (amount != 0.0) {
      amountController.text = (amount - 10).toStringAsFixed(0);
    }
  }
}
