import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/views/gold_store/gold_store_screen.dart';
import 'package:goldinvest/views/my_invest/my_invest_screen.dart';

import '../../controller/my_investment/my_investment_controller.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/tab/tab.dart';
import '../gold_invest/gold_invest_screen.dart';
part 'investment_mobile_screen.dart';

class InvestmentScreen extends StatelessWidget {
  final int selectTab;

  const InvestmentScreen({super.key, required this.selectTab});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: InvestmentMobileScreen(
        selectedPage: selectTab,
      ),
    );
  }
}
