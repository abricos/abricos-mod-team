var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    var MemberListRowWidgetExt = function(){
    };
    MemberListRowWidgetExt.ATTRS = {
        member: {value: null}
    };
    MemberListRowWidgetExt.prototype = {
        buildTData: function(){
            return this.get('member').toReplace();
        },
        onInitAppWidget: function(err, appInstance, options){
            this.appURLUpdate();
        },
    };
    NS.MemberListRowWidgetExt = MemberListRowWidgetExt;

    NS.MemberListRowWidget = Y.Base.create('MemberListRowWidget', SYS.AppWidget, [
        NS.MemberListRowWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'},
        },
        CLICKS: {}
    });

    var MemberListWidgetExt = function(){
    };
    MemberListWidgetExt.ATTRS = {
        teamApp: NS.ATTRIBUTE.teamApp,
        teamid: NS.ATTRIBUTE.teamid,
        memberList: {value: null},
        memberListFilter: NS.ATTRIBUTE.memberListFilter,
    };
    MemberListWidgetExt.prototype = {
        onInitAppWidget: function(err, appInstance){
            this._wsList = [];

            var memberList = this.get('memberList');
            if (!memberList){
                this.reloadMemberList();
            } else {
                this._onLoadMemberList(memberList);
            }
        },
        destructor: function(){
            this._cleanMemberList();
        },
        reloadMemberList: function(){
            var teamApp = this.get('teamApp'),
                filter = this.get('memberListFilter');

            this.set('waiting', true);
            teamApp.memberList(filter, function(err, result){
                var memberList = err ? null : result.memberList;
                this._onLoadMemberList(memberList);
            }, this);
        },
        _onLoadMemberList: function(memberList){
            this.set('waiting', false);
            this.set('memberList', memberList);

            if (!memberList){
                return this.onLoadMemberList(null);
            }

            var appInstance = this.get('appInstance'),
                ownerModule = appInstance.get('moduleName'),
                tp = this.template,
                wsList = this._cleanMemberList(),
                MemberListRowWidget = Brick.mod[ownerModule].MemberListRowWidget || NS.MemberListRowWidget;

            memberList.each(function(member){
                var w = new MemberListRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    member: member
                });
                wsList[wsList.length] = w;
            });

            return this.onLoadMemberList(memberList);
        },
        onLoadMemberList: function(memberList){
        },
        _cleanMemberList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },

    };
    NS.MemberListWidgetExt = MemberListWidgetExt;

    NS.MemberListWidget = Y.Base.create('memberListWidget', SYS.AppWidget, [
        NS.MemberListWidgetExt
    ], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
        },
        CLICKS: {}
    });
};
