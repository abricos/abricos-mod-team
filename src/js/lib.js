var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['base.js']}
    ]
};
Component.entryPoint = function(NS){
    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.initCallbackFire();
            }, this);
        },
    }, [], {
        APPS: {
            uprofile: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: true},
            Team: {value: NS.Team},
            TeamList: {value: NS.TeamList},
            Member: {value: NS.Member},
            MemberList: {value: NS.MemberList},
            Config: {value: NS.Config}
        },
        REQS: {
            team: {
                args: ['teamid'],
                type: 'model:Team',
                onResponse: function(team){
                    var memberList = team.get('members'),
                        userIds = memberList.toArray('userid', {distinct: true});

                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            callback.call(context || null);
                        }, context);
                    };
                }
            },
            teamList: {
                args: ['filter'],
                type: 'modelList:TeamList'
            },
            memberList: {
                args: ['filter'],
                type: 'modelList:MemberList',
                onResponse: function(memberList){
                    var userIds = memberList.toArray('userid', {distinct: true});

                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            callback.call(context || null);
                        }, context);
                    };
                }
            },
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
        }
    });
};