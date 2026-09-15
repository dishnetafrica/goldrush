import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/widgets/gold_invest_widgets/gold_invest_widget.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/invest/invest_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/gold_invest_card_info_widget/custom_card.dart';

part 'gold_invest_mobile_screen.dart';

class GoldInvestScreen extends StatelessWidget {
  const GoldInvestScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: GoldInvestMobileScreen());
  }
}
