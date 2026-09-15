part of 'investment_screen.dart';

class InvestmentMobileScreen extends StatelessWidget {
  int selectedPage;
  InvestmentMobileScreen({
    super.key,
    required this.selectedPage,
  });

  final tabController = Get.put(MyInvestmentController());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          Strings.invest,
          showBackButton: true,
          leading: BackButtonWidget(
            onTap: () => Get.toNamed(Routes.home),
          ),
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  dynamic _bodyWidget(context) {
    return _tabBarWithView(context);
  }

  Container _tabBarWithView(context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
      ),
      child: DefaultTabController(
        initialIndex: selectedPage,
        length: 3,
        child: Column(
          children: [
            Container(
              padding: EdgeInsets.only(
                left: Dimensions.paddingSize * 0.5,
                right: Dimensions.paddingSize * 0.5,
              ),
              height: Dimensions.heightSize * 3.5,
              color: CustomColor.blackColor.withValues(alpha: 0.05),
              width: double.infinity,
              child: TabBar(
                onTap: (index) {
                  tabController.setTabIndex(index);
                },
                splashFactory: NoSplash.splashFactory,
                overlayColor: WidgetStateProperty.resolveWith<Color?>((states) {
                  if (states.contains(WidgetState.pressed)) {
                    return Colors.red.withValues(alpha: 0.2);
                  }
                  return null;
                }),
                dividerHeight: 0,
                indicatorColor: CustomColor.blackColor.withValues(alpha: 0.05),
                indicator: BoxDecoration(
                  color: CustomColor.primaryLightColor,
                  borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
                ),
                unselectedLabelColor: CustomColor.blackColor.withValues(alpha: 0.5),
                labelColor: CustomColor.whiteColor,
                labelPadding: const EdgeInsets.symmetric(
                    horizontal: 6.0, vertical: 0.00001),
                tabs: const [
                  Tab(child: CustomTabWidget(title: Strings.goldInvest)),
                  Tab(child: CustomTabWidget(title: Strings.goldStore)),
                  Tab(child: CustomTabWidget(title: Strings.myInvest)),
                ],
              ),
            ),
            const Expanded(
              child: TabBarView(
                children: [
                  GoldInvestScreen(),
                  GoldStoreScreen(),
                  MyInvestScreen()
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
