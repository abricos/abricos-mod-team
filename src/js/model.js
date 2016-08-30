var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['appModel.js']}
    ]
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Team = Y.Base.create('team', SYS.AppModel, [], {
        structureName: 'Team'
    }, {
        ATTRS: {
            extends: {value: {}}
        }
    });

    NS.TeamList = Y.Base.create('teamList', SYS.AppModelList, [], {
        appItem: NS.Team,
    });

    NS.Member = Y.Base.create('member', SYS.AppModel, [], {
        structureName: 'Member'
    });

    NS.MemberList = Y.Base.create('memberList', SYS.AppModelList, [], {
        appItem: NS.Member,
    });

    NS.Config = Y.Base.create('config', SYS.AppModel, [], {
        structureName: 'Config'
    });
};
