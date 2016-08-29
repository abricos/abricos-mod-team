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

    var MemberRowWidgetExt = function(){
    };
    MemberRowWidgetExt.ATTRS = {
        member: {value: null}
    };
    MemberRowWidgetExt.prototype = {
        buildTData: function(){
            return this.get('member').toJSON(true);
        },
        onInitAppWidget: function(err, appInstance, options){
            this.appURLUpdate();
        },
    };
    NS.MemberRowWidgetExt = MemberRowWidgetExt;

    NS.MemberRowWidget = Y.Base.create('MemberRowWidget', SYS.AppWidget, [
        NS.MemberRowWidgetExt
    ], {
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'},
        },
        CLICKS: {}
    });

    NS.MemberListWidget = Y.Base.create('memberListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this._wsList = [];
            this.reloadMemberList();
        },
        destructor: function(){
            this._cleanMemberList();
        },
        reloadMemberList: function(){
            this.set('waiting', true);

            var appInstance = this.get('appInstance'),
                filter = {
                    ownerModule: this.get('ownerModule')
                };

            appInstance.memberList(filter, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('memberList', result.memberList);

                    var modules = result.memberList.toArray('module', {distinct: true});
                    NS.uses(modules, 'memberList', function(){
                        this.renderMemberList();
                    }, this);
                }
            }, this);
        },
        _cleanMemberList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
        renderMemberList: function(){
            var memberList = this.get('memberList');
            if (!memberList){
                return;
            }

            var tp = this.template,
                wsList = this._cleanMemberList();

            memberList.each(function(member){
                var ownerModule = member.get('module'),
                    MemberRowWidget = Brick.mod[ownerModule].MemberRowWidget || NS.MemberRowWidget;

                var w = new MemberRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    member: member
                });
                wsList[wsList.length] = w;
            });
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
            memberList: {value: null},
            ownerModule: {value: ''}
        },
        CLICKS: {}
    });

};