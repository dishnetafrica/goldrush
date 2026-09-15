import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';

abstract class DropdownMenuModel {
  String get title;
}

class CustomDropdownMenu<T> extends StatefulWidget {
  final RxString selectMethod;
  final List<T> itemsList;
  final void Function(T?)? onChanged;
  final bool showSearchField;
  final Decoration? decoration;
  final Color? labelColor;
  final Color? dropdownIconColor;
  final Widget? leading;
  final String hintText;
  final String? iconPath;
  const CustomDropdownMenu({
    required this.itemsList,
    required this.selectMethod,
    this.onChanged,
    this.decoration,
    this.labelColor,
    this.dropdownIconColor,
    this.leading,
    this.showSearchField = false,
    super.key,
    required this.hintText,
    this.iconPath,
  });

  @override
  // ignore: library_private_types_in_public_api
  _CustomDropdownMenuState<T> createState() => _CustomDropdownMenuState<T>();
}

class _CustomDropdownMenuState<T> extends State<CustomDropdownMenu<T>>
    with SingleTickerProviderStateMixin {
  bool isDropdownOpened = false;
  final LayerLink _layerLink = LayerLink();
  TextEditingController searchController = TextEditingController();
  List<T> filteredItems = [];
  OverlayEntry? _overlayEntry;
  FocusNode? focusNode;

  @override
  void initState() {
    super.initState();
    filteredItems = widget.itemsList;
    focusNode = FocusNode();
  }

  @override
  void dispose() {
    searchController.dispose();
    super.dispose();
  }

  void _toggleDropdown() {
    if (isDropdownOpened) {
      _closeDropdown();
    } else {
      _openDropdown();
    }
  }

  void _openDropdown() {
    RenderBox renderBox = context.findRenderObject() as RenderBox;
    var size = renderBox.size;
    var screenHeight = MediaQuery.of(context).size.height;
    var spaceBelow =
        screenHeight - renderBox.localToGlobal(Offset.zero).dy - size.height;
    var spaceAbove = renderBox.localToGlobal(Offset.zero).dy;

    double itemHeight = 56.0;
    int maxVisibleItems = 6;
    double dropdownHeight =
        _calculateDropdownHeight(itemHeight, maxVisibleItems);

    double spaceThresholdAbove = dropdownHeight - 20.0;
    bool openUpwards =
        spaceBelow < dropdownHeight && spaceAbove > spaceThresholdAbove;

    if (openUpwards && spaceAbove < dropdownHeight) {
      dropdownHeight = spaceAbove - 10.0;
    }

    _overlayEntry = _createOverlayEntry(
        openUpwards: openUpwards, dropdownHeight: dropdownHeight);
    Overlay.of(context).insert(_overlayEntry!);

    setState(() {
      isDropdownOpened = true;
    });
  }

  void _closeDropdown() {
    _overlayEntry?.remove();
    setState(() {
      isDropdownOpened = false;
      searchController.clear();
      filteredItems = widget.itemsList;
    });
  }

  OverlayEntry _createOverlayEntry(
      {bool openUpwards = false, double dropdownHeight = 200.0}) {
    RenderBox renderBox = context.findRenderObject() as RenderBox;
    var size = renderBox.size;

    return OverlayEntry(
      builder: (context) => GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: _closeDropdown,
        child: Stack(
          children: [
            Positioned(
              width: size.width,
              child: CompositedTransformFollower(
                link: _layerLink,
                showWhenUnlinked: false,
                offset: Offset(
                  0.0,
                  openUpwards
                      ? -dropdownHeight - size.height * 0.08
                      : size.height,
                ),
                child: _buildDropdownList(dropdownHeight),
              ),
            ),
          ],
        ),
      ),
    );
  }

  double _calculateDropdownHeight(double itemHeight, int maxVisibleItems) {
    return filteredItems.length <= maxVisibleItems
        ? filteredItems.length * itemHeight
        : maxVisibleItems * itemHeight;
  }

  Widget _buildDropdownList(double dropdownHeight) {
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Material(
        elevation: 2,
        color: CustomColor.whiteColor,
        borderRadius: BorderRadius.circular(Dimensions.radius),
        child: Container(
          margin: EdgeInsets.symmetric(
            vertical: Dimensions.marginSizeVertical * 0.5,
          ),
          decoration: BoxDecoration(
            color: CustomColor.whiteColor,
            borderRadius: BorderRadius.circular(Dimensions.radius),
          ),
          child: Column(
            children: [
              if (widget.showSearchField) _buildSearchField(),
              if (filteredItems.isNotEmpty)
                _buildFilteredItemsList(dropdownHeight)
              else
                Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Text(
                    DynamicLanguage.key(Strings.noDataFound),
                    style: CustomStyle.lightHeading3TextStyle,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSearchField() {
    return Obx(
      () => Padding(
        padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize * 0.5),
        child: TextField(
          controller: searchController,
          onChanged: (value) {
            setState(() {
              filteredItems = widget.itemsList
                  .where((item) => _getItemTitle(item)
                      .toLowerCase()
                      .contains(value.toLowerCase()))
                  .toList();
            });
          },
          cursorColor: CustomColor.primaryLightColor,
          decoration: InputDecoration(
            hintText: 'Search',
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFilteredItemsList(double dropdownHeight) {
    return ConstrainedBox(
      constraints: BoxConstraints(maxHeight: dropdownHeight),
      child: ListView.builder(
        padding: EdgeInsets.zero,
        shrinkWrap: true,
        itemCount: filteredItems.length,
        itemBuilder: (context, index) {
          final value = filteredItems[index];
          return _buildDropdownItem(value);
        },
      ),
    );
  }

  Widget _buildDropdownItem(T value) {
    final itemTitle = _getItemTitle(value);

    return GestureDetector(
      onTap: () {
        widget.onChanged?.call(value);
        widget.selectMethod.value = itemTitle;
        _closeDropdown();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: widget.selectMethod.value == itemTitle
              ? CustomColor.primaryLightColor.withValues(alpha: 0.6)
              : Colors.transparent,
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 13),
          child: Text(
            itemTitle,
            style: CustomStyle.lightHeading4TextStyle.copyWith(
              color:
                  widget.selectMethod.value == itemTitle ? Colors.white : null,
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
        ),
      ),
    );
  }

  Widget _buildDropdownButton(BuildContext context) {
    var screenSize = MediaQuery.of(context).size;
    return GestureDetector(
      onTap: _toggleDropdown,
      child: Container(
        height: Dimensions.inputBoxHeight * 0.90,
        decoration: widget.decoration ??
            BoxDecoration(
              color: const Color(0xffF5F5F5),
              borderRadius: BorderRadius.circular(Dimensions.radius * 1.2),
            ),
        child: Padding(
          padding: const EdgeInsets.only(left: 5, right: 20),
          child: Row(
            children: [
              Padding(
                padding: EdgeInsets.only(left: Dimensions.paddingSize * 0.2),
                child: CustomImageWidget(
                  path: widget.iconPath!,
                  color: focusNode!.hasFocus
                      ? CustomColor.blackColor
                      : CustomColor.liteBlack2,
                ),
              ),
              Expanded(
                child: Row(
                  mainAxisAlignment: mainSpaceBet,
                  children: [
                    if (widget.leading != null)
                      Container(
                        child: widget.leading,
                      ),
                    Expanded(
                        child: Padding(
                            padding: EdgeInsets.only(
                                left: widget.leading == null
                                    ? Dimensions.paddingSize * 0.4
                                    : Dimensions.paddingSize * 0.1),
                            child: Text(
                              widget.selectMethod.value.isEmpty
                                  ? DynamicLanguage.key(widget.hintText)
                                  : DynamicLanguage.key(
                                      widget.selectMethod.value),
                              style: GoogleFonts.manrope(
                                fontSize: screenSize.width * 0.04,
                                fontWeight: FontWeight.w600,
                                color: widget.selectMethod.value.isEmpty
                                    ? (widget.labelColor ??
                                        CustomColor.primaryLightTextColor
                                            .withValues(alpha: 0.2))
                                    : (isDropdownOpened
                                        ? CustomColor.blackColor
                                        : null),
                              ),
                              overflow: TextOverflow.ellipsis,
                              maxLines: 1,
                            ))),
                    Icon(
                      isDropdownOpened
                          ? Icons.arrow_drop_up
                          : Icons.arrow_drop_down,
                      color: widget.dropdownIconColor ??
                          (isDropdownOpened
                              ? CustomColor.blackColor
                              : CustomColor.liteBlack2),
                    ),
                  ],
                ),
              )
            ],
          ),
        ),
      ),
    );
  }

  String _getItemTitle(T item) {
    if (item is DropdownMenuModel) {
      return item.title;
    } else if (item is String) {
      return item;
    } else {
      throw ArgumentError('Unsupported item type: ${item.runtimeType}');
    }
  }

  @override
  Widget build(BuildContext context) {
    return CompositedTransformTarget(
      link: _layerLink,
      child: _buildDropdownButton(context),
    );
  }
}
