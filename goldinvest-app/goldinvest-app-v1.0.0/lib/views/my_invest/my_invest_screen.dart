import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:goldinvest/utils/responsive_layout.dart';
import 'package:goldinvest/widgets/my_invest_widgets/my_invest_widgets.dart';
import 'package:intl/intl.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/utils/custom_loading_api.dart';
import '../../controller/my_investment/my_investment_controller.dart';

import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/time_widget/time_widget.dart';
import '../../widgets/wallet_card/wallet_card.dart';
part 'my_invest_mobile_screen.dart';

class MyInvestScreen extends StatelessWidget {
  const MyInvestScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(mobileScaffold: MyInvestMobileScreen());
  }
}
